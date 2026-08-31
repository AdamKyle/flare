import { useCallback, useMemo, useState } from 'react';

import UseBatchCraftingDashboardDefinition, {
  ChartPoint,
  ChartSeriesDefinition,
} from './definitions/use-batch-crafting-dashboard-definition';
import useBatchCraftingLiveRefresh from './use-batch-crafting-live-refresh';
import useBatchCraftingRefresh from './use-batch-crafting-refresh';
import { ADMIN_MONITORING_CHART_COLORS } from '../../values/admin-monitoring-chart-colors';
import BatchCraftingLogsPage from '../api/definitions/batch-crafting-logs-page-definition';
import {
  ActiveBatchCrafter,
  BatchCraftingChartPoint,
  BatchCraftingFilters,
  BatchCraftingRunRow,
  BatchCraftingSummary,
  Paginated,
} from '../api/definitions/batch-crafting-monitoring-definition';
import { BatchCraftingMonitoringMessages } from '../api/enums/batch-crafting-monitoring-messages';
import { useBatchCraftingApi } from '../api/hooks/use-batch-crafting-api';
import createEmptyBatchCraftingLogsPage from '../utils/create-empty-batch-crafting-logs-page';
import createEmptyBatchCraftingPage from '../utils/create-empty-batch-crafting-page';
import { DEFAULT_BATCH_CRAFTING_FILTERS } from '../values/default-batch-crafting-filters';
import { EMPTY_BATCH_CRAFTING_SUMMARY } from '../values/empty-batch-crafting-summary';

/**
 * Owns every Batch Crafting monitoring data concern: fetch orchestration,
 * filters, pagination, and derived chart values. The dashboard component
 * only renders this hook's state; it never coordinates requests itself.
 */
export default function useBatchCraftingDashboard(): UseBatchCraftingDashboardDefinition {
  const {
    fetchBatchCraftingActive,
    fetchBatchCraftingChart,
    fetchBatchCraftingLogs,
    fetchBatchCraftingRuns,
    fetchBatchCraftingSummary,
  } = useBatchCraftingApi();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [days, setDays] = useState('7');
  const [summary, setSummary] = useState<BatchCraftingSummary>(
    EMPTY_BATCH_CRAFTING_SUMMARY
  );
  const [chartData, setChartData] = useState<BatchCraftingChartPoint[]>([]);
  const [active, setActive] = useState<ActiveBatchCrafter[]>([]);
  const [runs, setRuns] = useState<Paginated<BatchCraftingRunRow>>(
    createEmptyBatchCraftingPage()
  );
  const [filters, setFiltersState] = useState<BatchCraftingFilters>(
    DEFAULT_BATCH_CRAFTING_FILTERS
  );
  const [page, setPage] = useState(1);
  const [logs, setLogs] = useState<BatchCraftingLogsPage>(
    createEmptyBatchCraftingLogsPage()
  );
  const [logPage, setLogPage] = useState(1);
  const [logSeverity, setLogSeverityState] = useState('');

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const [summaryData, chart, activeData, runsData, logsData] =
        await Promise.all([
          fetchBatchCraftingSummary(days),
          fetchBatchCraftingChart(days),
          fetchBatchCraftingActive(),
          fetchBatchCraftingRuns(filters, page),
          fetchBatchCraftingLogs(logPage, logSeverity),
        ]);

      setSummary(summaryData);
      setChartData(chart);
      setActive(activeData);
      setRuns(runsData);
      setLogs(logsData);
    } catch {
      setError(BatchCraftingMonitoringMessages.Load);
    } finally {
      setLoading(false);
    }
  }, [
    days,
    fetchBatchCraftingActive,
    fetchBatchCraftingChart,
    fetchBatchCraftingLogs,
    fetchBatchCraftingRuns,
    fetchBatchCraftingSummary,
    filters,
    logPage,
    logSeverity,
    page,
  ]);

  useBatchCraftingRefresh(refresh);
  useBatchCraftingLiveRefresh(refresh);

  const chartPoints = useMemo<ChartPoint[]>(
    () =>
      chartData.map((point) => ({
        period: point.period,
        runs: point.runs,
        crafted: point.crafted,
        failed: point.failed,
      })),
    [chartData]
  );

  const chartSeries = useMemo<ChartSeriesDefinition[]>(
    () => [
      {
        key: 'runs',
        label: 'Runs',
        color: ADMIN_MONITORING_CHART_COLORS.indigo,
      },
      {
        key: 'crafted',
        label: 'Crafted',
        color: ADMIN_MONITORING_CHART_COLORS.emerald,
      },
      {
        key: 'failed',
        label: 'Failed',
        color: ADMIN_MONITORING_CHART_COLORS.rose,
      },
    ],
    []
  );

  const updateFilters = (nextFilters: BatchCraftingFilters): void => {
    setFiltersState(nextFilters);
    setPage(1);
  };

  const applyTableFilter = (
    nextFilters: Partial<BatchCraftingFilters>
  ): void => {
    setFiltersState({ ...DEFAULT_BATCH_CRAFTING_FILTERS, ...nextFilters });
    setPage(1);
  };

  const updateLogSeverity = (severity: string): void => {
    setLogSeverityState(severity);
    setLogPage(1);
  };

  return {
    loading,
    error,
    days,
    set_days: setDays,
    summary,
    chart_points: chartPoints,
    chart_series: chartSeries,
    active,
    runs,
    filters,
    update_filters: updateFilters,
    apply_table_filter: applyTableFilter,
    page,
    set_page: setPage,
    logs,
    log_page: logPage,
    set_log_page: setLogPage,
    log_severity: logSeverity,
    update_log_severity: updateLogSeverity,
  };
}
