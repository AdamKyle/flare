import { useCallback, useEffect, useState } from 'react';

import useFactionLoyaltyLiveRefresh from './use-faction-loyalty-live-refresh';
import {
  ActiveFactionLoyaltyRunner,
  FactionLoyaltyChartPoint,
  FactionLoyaltyFilters,
  FactionLoyaltyRunRow,
  FactionLoyaltySummary,
} from '../api/definitions/faction-loyalty-monitoring-definition';
import { useFactionLoyaltyApi } from '../api/hooks/use-faction-loyalty-api';
import factionLoyaltyPaginationAdapter from '../utils/faction-loyalty-pagination-adapter';
import { DAY_OPTIONS } from '../values/filter-options';

const DEFAULT_FILTERS: FactionLoyaltyFilters = {
  character_name: '',
  date_from: '',
  date_to: '',
  status: '',
  days: '7',
};

const EMPTY_SUMMARY: FactionLoyaltySummary = {
  total_runs: 0,
  active: 0,
  completed: 0,
};

/**
 * Owns every Faction Loyalty monitoring data concern: fetch orchestration,
 * filters, and pagination. The dashboard component only renders this
 * hook's state; it never coordinates requests itself.
 */
export default function useFactionLoyaltyDashboard() {
  const {
    fetchFactionLoyaltyActive,
    fetchFactionLoyaltyRuns,
    fetchFactionLoyaltySummary,
    fetchFactionLoyaltyChart,
  } = useFactionLoyaltyApi();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [active, setActive] = useState<ActiveFactionLoyaltyRunner[]>([]);
  const [runs, setRuns] = useState(
    factionLoyaltyPaginationAdapter<FactionLoyaltyRunRow>({
      data: [],
      current_page: 1,
      last_page: 1,
      total: 0,
    })
  );
  const [summary, setSummary] = useState<FactionLoyaltySummary>(EMPTY_SUMMARY);
  const [chart, setChart] = useState<FactionLoyaltyChartPoint[]>([]);
  const [filters, setFilters] =
    useState<FactionLoyaltyFilters>(DEFAULT_FILTERS);
  const [page, setPage] = useState(1);
  const [days, setDays] = useState('7');

  const refresh = useCallback(async () => {
    setError('');

    try {
      const [activeData, runsData, summaryData, chartData] = await Promise.all([
        fetchFactionLoyaltyActive(),
        fetchFactionLoyaltyRuns(filters, page),
        fetchFactionLoyaltySummary(days),
        fetchFactionLoyaltyChart(days),
      ]);

      setActive(activeData);
      setRuns(runsData);
      setSummary(summaryData);
      setChart(chartData);
    } catch {
      setError('Faction loyalty monitoring data could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, [
    days,
    fetchFactionLoyaltyActive,
    fetchFactionLoyaltyChart,
    fetchFactionLoyaltyRuns,
    fetchFactionLoyaltySummary,
    filters,
    page,
  ]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  useFactionLoyaltyLiveRefresh(refresh);

  const applyTableFilter = (
    nextFilters: Partial<FactionLoyaltyFilters>
  ): void => {
    setFilters({ ...DEFAULT_FILTERS, ...nextFilters });
    setPage(1);
  };

  const updateFilters = (nextFilters: FactionLoyaltyFilters): void => {
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
