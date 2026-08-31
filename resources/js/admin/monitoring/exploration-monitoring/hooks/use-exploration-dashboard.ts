import { useCallback, useEffect, useState } from 'react';

import useExplorationLiveRefresh from './use-exploration-live-refresh';
import {
  ActiveExplorer,
  ExplorationChartPoint,
  ExplorationFilters,
  ExplorationLogRow,
  ExplorationSummary,
} from '../api/definitions/exploration-monitoring-definition';
import { useExplorationApi } from '../api/hooks/use-exploration-api';
import explorationPaginationAdapter from '../utils/exploration-pagination-adapter';
import { DAY_OPTIONS } from '../values/filter-options';

const EMPTY_SUMMARY: ExplorationSummary = {
  total_runs: 0,
  stopped_by_player: 0,
  total_kills: 0,
  total_xp_gained: 0,
  total_skill_xp_gained: 0,
};

const DEFAULT_FILTERS: ExplorationFilters = {
  character_name: '',
  stopped_reason: '',
  stopped_by_player: false,
  date_from: '',
  date_to: '',
  days: '7',
};

/**
 * Owns every Exploration monitoring data concern: fetch orchestration,
 * filters, and pagination. The dashboard component only renders this
 * hook's state; it never coordinates requests itself.
 */
export default function useExplorationDashboard() {
  const {
    fetchExplorationActive,
    fetchExplorationLogs,
    fetchExplorationSummary,
    fetchExplorationChart,
  } = useExplorationApi();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeExplorers, setActiveExplorers] = useState<ActiveExplorer[]>([]);
  const [logs, setLogs] = useState(
    explorationPaginationAdapter<ExplorationLogRow>({
      data: [],
      current_page: 1,
      last_page: 1,
      total: 0,
    })
  );
  const [summary, setSummary] = useState<ExplorationSummary>(EMPTY_SUMMARY);
  const [chart, setChart] = useState<ExplorationChartPoint[]>([]);
  const [filters, setFilters] = useState<ExplorationFilters>(DEFAULT_FILTERS);
  const [logPage, setLogPage] = useState(1);
  const [days, setDays] = useState('7');

  const refresh = useCallback(async () => {
    setError('');

    try {
      const [active, logsData, summaryData, chartData] = await Promise.all([
        fetchExplorationActive(),
        fetchExplorationLogs(filters, logPage),
        fetchExplorationSummary(days),
        fetchExplorationChart(days),
      ]);

      setActiveExplorers(active);
      setLogs(logsData);
      setSummary(summaryData);
      setChart(chartData);
    } catch {
      setError('Exploration monitoring data could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, [
    days,
    fetchExplorationActive,
    fetchExplorationChart,
    fetchExplorationLogs,
    fetchExplorationSummary,
    filters,
    logPage,
  ]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  useExplorationLiveRefresh(refresh);

  const applyTableFilter = (nextFilters: Partial<ExplorationFilters>): void => {
    setFilters({ ...DEFAULT_FILTERS, ...nextFilters });
    setLogPage(1);
  };

  const updateFilters = (nextFilters: ExplorationFilters): void => {
    setFilters(nextFilters);
    setLogPage(1);
  };

  return {
    loading,
    error,
    active_explorers: activeExplorers,
    logs,
    summary,
    chart,
    filters,
    update_filters: updateFilters,
    apply_table_filter: applyTableFilter,
    log_page: logPage,
    set_log_page: setLogPage,
    days,
    set_days: setDays,
    day_options: DAY_OPTIONS,
  };
}
