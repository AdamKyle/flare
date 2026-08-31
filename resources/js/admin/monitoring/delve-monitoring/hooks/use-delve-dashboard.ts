import { useCallback, useEffect, useState } from 'react';

import useDelveMonitoringLiveRefresh from './use-delve-live-refresh';
import {
  ActiveDelveRunner,
  DelveChartPoint,
  DelveFilters,
  DelveRunRow,
  DelveSummary,
} from '../api/definitions/delve-monitoring-definition';
import { useDelveApi } from '../api/hooks/use-delve-api';
import delvePaginationAdapter from '../utils/delve-pagination-adapter';
import { DAY_OPTIONS } from '../values/filter-options';

const DEFAULT_FILTERS: DelveFilters = {
  character_name: '',
  date_from: '',
  date_to: '',
  status: '',
  outcome: '',
};

const EMPTY_SUMMARY: DelveSummary = {
  total_runs: 0,
  active: 0,
  completed: 0,
  total_survived: 0,
  total_died: 0,
  total_timeout: 0,
};

/**
 * Owns every Delve monitoring data concern: fetch orchestration, filters,
 * and pagination. The dashboard component only renders this hook's state;
 * it never coordinates requests itself.
 */
export default function useDelveDashboard() {
  const {
    fetchDelveActive,
    fetchDelveRuns,
    fetchDelveSummary,
    fetchDelveChart,
  } = useDelveApi();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [active, setActive] = useState<ActiveDelveRunner[]>([]);
  const [runs, setRuns] = useState(
    delvePaginationAdapter<DelveRunRow>({
      data: [],
      current_page: 1,
      last_page: 1,
      total: 0,
    })
  );
  const [summary, setSummary] = useState<DelveSummary>(EMPTY_SUMMARY);
  const [chart, setChart] = useState<DelveChartPoint[]>([]);
  const [filters, setFilters] = useState<DelveFilters>(DEFAULT_FILTERS);
  const [page, setPage] = useState(1);
  const [days, setDays] = useState('7');

  const refresh = useCallback(async () => {
    setError('');

    try {
      const [activeData, runsData, summaryData, chartData] = await Promise.all([
        fetchDelveActive(),
        fetchDelveRuns(filters, page),
        fetchDelveSummary(days),
        fetchDelveChart(days),
      ]);

      setActive(activeData);
      setRuns(runsData);
      setSummary(summaryData);
      setChart(chartData);
    } catch {
      setError('Delve monitoring data could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, [
    days,
    fetchDelveActive,
    fetchDelveChart,
    fetchDelveRuns,
    fetchDelveSummary,
    filters,
    page,
  ]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  useDelveMonitoringLiveRefresh(refresh);

  const applyTableFilter = (nextFilters: Partial<DelveFilters>): void => {
    setFilters({ ...DEFAULT_FILTERS, ...nextFilters });
    setPage(1);
  };

  const updateFilters = (nextFilters: DelveFilters): void => {
    setFilters(nextFilters);
    setPage(1);
  };

  return {
    loading,
    error,
    active,
    runs,
    summary,
    chart,
    filters,
    update_filters: updateFilters,
    apply_table_filter: applyTableFilter,
    page,
    set_page: setPage,
    days,
    set_days: setDays,
    day_options: DAY_OPTIONS,
  };
}
