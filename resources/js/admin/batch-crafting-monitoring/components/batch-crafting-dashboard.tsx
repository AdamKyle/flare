import React, {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';

import MonitorCard from './monitor-card';
import { ChannelType } from '../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../websocket-handler/hooks/use-websocket';
import MonitoringStatusChart from '../../monitoring/components/monitoring-status-chart';
import { ADMIN_MONITORING_CHART_COLORS } from '../../monitoring/values/admin-monitoring-chart-colors';
import BatchCraftingLogsPage from '../api/definitions/batch-crafting-logs-page-definition';
import {
  ActiveBatchCrafter,
  BatchCraftingChartPoint,
  BatchCraftingFilters,
  BatchCraftingRunRow,
  BatchCraftingSummary,
  Paginated,
} from '../api/definitions/batch-crafting-monitoring-definition';
import { useBatchCraftingApi } from '../api/hooks/use-batch-crafting-api';
import { BatchCraftingWebsocketEvents } from '../enums/batch-crafting-websocket-events';
import BatchCraftingMonitoringUpdatedPayload from '../types/batch-crafting-monitoring-updated-payload';
import BatchCraftingSummaryCard from '../types/batch-crafting-summary-card';

const DAY_OPTIONS = [
  { value: '1', label: '1 day' },
  { value: '7', label: '7 days' },
  { value: '14', label: '14 days' },
  { value: '30', label: '30 days' },
  { value: '180', label: '6 months' },
  { value: '365', label: '1 year' },
];

const defaultFilters: BatchCraftingFilters = {
  character_name: '',
  date_from: '',
  date_to: '',
  status: '',
  batch_type: '',
};

const emptySummary: BatchCraftingSummary = {
  total_runs: 0,
  active: 0,
  completed: 0,
  cancelled: 0,
  total_crafted: 0,
  total_failed: 0,
};

const emptyPage = <T,>(): Paginated<T> => ({
  data: [],
  current_page: 1,
  last_page: 1,
  total: 0,
});

const emptyLogsPage = (): BatchCraftingLogsPage => ({
  data: [],
  current_page: 1,
  last_page: 1,
  total: 0,
  next_cursor: null,
});

function humanizeStatus(row: BatchCraftingRunRow): string {
  if (row.cancelled_at) return 'Cancelled';
  if (row.completed_at) return row.ended_reason ?? 'Completed';
  return 'Running';
}

export default function BatchCraftingDashboard() {
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
  const [summary, setSummary] = useState(emptySummary);
  const [chartData, setChartData] = useState<BatchCraftingChartPoint[]>([]);
  const [active, setActive] = useState<ActiveBatchCrafter[]>([]);
  const [runs, setRuns] = useState<Paginated<BatchCraftingRunRow>>(emptyPage());
  const [filters, setFiltersState] = useState(defaultFilters);
  const [page, setPage] = useState(1);
  const [logs, setLogs] = useState(emptyLogsPage());
  const [logPage, setLogPage] = useState(1);
  const [logSeverity, setLogSeverityState] = useState('');
  const debounceTimer = useRef<ReturnType<typeof setTimeout> | undefined>(
    undefined
  );

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
      setError('Failed to load batch crafting monitoring data.');
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

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const handleMonitoringUpdate = useCallback(() => {
    clearTimeout(debounceTimer.current);
    debounceTimer.current = setTimeout(() => void refresh(), 500);
  }, [refresh]);

  useWebsocket<BatchCraftingMonitoringUpdatedPayload>({
    url: BatchCraftingWebsocketEvents.CHANNEL,
    params: {},
    type: ChannelType.PRIVATE,
    channelName: BatchCraftingWebsocketEvents.UPDATED,
    onEvent: handleMonitoringUpdate,
  });

  useEffect(() => () => clearTimeout(debounceTimer.current), []);

  const applyTableFilter = (nextFilters: Partial<BatchCraftingFilters>) => {
    setFiltersState({ ...defaultFilters, ...nextFilters });
    setPage(1);
    window.setTimeout(() => {
      document.getElementById('batch-crafting-runs-table')?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
      });
    }, 0);
  };

  const updateFilters = (nextFilters: BatchCraftingFilters) => {
    setFiltersState(nextFilters);
    setPage(1);
  };

  const updateLogSeverity = (severity: string) => {
    setLogSeverityState(severity);
    setLogPage(1);
  };

  const chartPoints = useMemo(
    () =>
      chartData.map((point) => ({
        period: point.period,
        runs: point.runs,
        crafted: point.crafted,
        failed: point.failed,
      })),
    [chartData]
  );
  const chartSeries = useMemo(
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
  const levelColor = (level: string): string => {
    const normalizedLevel = level.toUpperCase();

    if (['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(normalizedLevel)) {
      return 'text-red-700 dark:text-red-300';
    }

    if (normalizedLevel === 'WARNING') {
      return 'text-amber-700 dark:text-amber-300';
    }

    return 'text-gray-700 dark:text-gray-200';
  };

  return (
    <div className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
      {loading && (
        <p role="status" aria-live="polite">
          Loading batch crafting monitoring data…
        </p>
      )}
      {error !== null && (
        <p
          className="rounded border border-red-400 bg-red-50 p-3 text-red-800 dark:bg-red-950 dark:text-red-100"
          role="alert"
        >
          {error}
        </p>
      )}

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        {(
          [
            {
              label: 'Total Runs',
              value: summary.total_runs,
              filter: null,
            },
            {
              label: 'Active',
              value: summary.active,
              filter: { status: 'active' },
            },
            {
              label: 'Completed',
              value: summary.completed,
              filter: { status: 'completed' },
            },
            {
              label: 'Cancelled',
              value: summary.cancelled,
              filter: { status: 'cancelled' },
            },
            {
              label: 'Total Crafted',
              value: summary.total_crafted,
              filter: null,
            },
            {
              label: 'Total Failed',
              value: summary.total_failed,
              filter: null,
            },
          ] as BatchCraftingSummaryCard[]
        ).map(({ label, value, filter }) => {
          return (
            <MonitorCard
              key={label}
              onClick={
                filter !== null ? () => applyTableFilter(filter) : undefined
              }
              ariaLabel={
                filter !== null ? `Filter recent runs by ${label}` : undefined
              }
            >
              <div className="text-sm text-gray-600 dark:text-gray-300">
                {label}
              </div>
              <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {value}
              </div>
            </MonitorCard>
          );
        })}
      </div>

      <div>
        <label className="mb-3 block text-sm font-medium">
          Period
          <select
            className="ml-2 rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
            value={days}
            onChange={(e) => setDays(e.target.value)}
          >
            {DAY_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </label>
        <MonitoringStatusChart
          title="Batch Crafting Over Time"
          description="Runs, items crafted, and failures per day."
          points={chartPoints}
          series={chartSeries}
        />
      </div>

      {active.length > 0 ? (
        <MonitorCard>
          <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
            Active Batches
          </h2>
          <div className="grid gap-3 md:hidden">
            {active.map((row) => (
              <div
                key={row.character_id}
                className="rounded border border-gray-200 p-3 text-sm dark:border-gray-700"
              >
                <div className="font-semibold">{row.character_name ?? '—'}</div>
                <dl className="mt-2 grid grid-cols-2 gap-2 text-xs">
                  <div>
                    <dt className="text-gray-500 dark:text-gray-400">Type</dt>
                    <dd>{row.batch_type}</dd>
                  </div>
                  <div>
                    <dt className="text-gray-500 dark:text-gray-400">
                      Disposition
                    </dt>
                    <dd>{row.disposition}</dd>
                  </div>
                  <div>
                    <dt className="text-gray-500 dark:text-gray-400">
                      Crafted
                    </dt>
                    <dd>{row.crafted_count}</dd>
                  </div>
                  <div>
                    <dt className="text-gray-500 dark:text-gray-400">Failed</dt>
                    <dd>{row.failed_count}</dd>
                  </div>
                </dl>
              </div>
            ))}
          </div>
          <div className="hidden overflow-x-auto md:block">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-gray-200 dark:border-gray-700">
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Character
                  </th>
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Type
                  </th>
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Disposition
                  </th>
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Started
                  </th>
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Crafted
                  </th>
                  <th scope="col" className="py-2 font-semibold">
                    Failed
                  </th>
                </tr>
              </thead>
              <tbody>
                {active.map((row) => (
                  <tr
                    key={row.character_id}
                    className="border-b border-gray-100 dark:border-gray-800"
                  >
                    <td className="py-2 pr-4">{row.character_name ?? '—'}</td>
                    <td className="py-2 pr-4">{row.batch_type}</td>
                    <td className="py-2 pr-4">{row.disposition}</td>
                    <td className="py-2 pr-4">{row.started_at ?? '—'}</td>
                    <td className="py-2 pr-4">{row.crafted_count}</td>
                    <td className="py-2">{row.failed_count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </MonitorCard>
      ) : null}

      <div id="batch-crafting-runs-table">
        <MonitorCard>
          <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
            Recent Runs
          </h2>
          <div className="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label className="text-sm font-medium">
              Character name
              <input
                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                type="text"
                value={filters.character_name}
                onChange={(e) => {
                  updateFilters({
                    ...filters,
                    character_name: e.target.value,
                  });
                }}
              />
            </label>
            <label className="text-sm font-medium">
              Date from
              <input
                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                type="date"
                value={filters.date_from}
                onChange={(e) => {
                  updateFilters({
                    ...filters,
                    date_from: e.target.value,
                  });
                }}
              />
            </label>
            <label className="text-sm font-medium">
              Date to
              <input
                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                type="date"
                value={filters.date_to}
                onChange={(e) => {
                  updateFilters({
                    ...filters,
                    date_to: e.target.value,
                  });
                }}
              />
            </label>
            <label className="text-sm font-medium">
              Batch type
              <input
                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                type="text"
                value={filters.batch_type}
                onChange={(e) => {
                  updateFilters({
                    ...filters,
                    batch_type: e.target.value,
                  });
                }}
              />
            </label>
          </div>
          <div className="grid gap-3 md:hidden">
            {runs.data.length === 0 ? (
              <p className="py-4 text-center text-gray-500 dark:text-gray-400">
                No runs found.
              </p>
            ) : (
              runs.data.map((row) => (
                <div
                  key={row.id}
                  className="rounded border border-gray-200 p-3 text-sm dark:border-gray-700"
                >
                  <div className="font-semibold">
                    {row.character?.name ?? '—'}
                  </div>
                  <dl className="mt-2 grid grid-cols-2 gap-2 text-xs">
                    <div>
                      <dt className="text-gray-500 dark:text-gray-400">Type</dt>
                      <dd>{row.batch_type}</dd>
                    </div>
                    <div>
                      <dt className="text-gray-500 dark:text-gray-400">
                        Status
                      </dt>
                      <dd>{humanizeStatus(row)}</dd>
                    </div>
                    <div>
                      <dt className="text-gray-500 dark:text-gray-400">
                        Crafted
                      </dt>
                      <dd>{row.crafted_count}</dd>
                    </div>
                    <div>
                      <dt className="text-gray-500 dark:text-gray-400">
                        Failed
                      </dt>
                      <dd>{row.failed_count}</dd>
                    </div>
                  </dl>
                </div>
              ))
            )}
          </div>
          <div className="hidden overflow-x-auto md:block">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-gray-200 dark:border-gray-700">
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Character
                  </th>
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Type
                  </th>
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Disposition
                  </th>
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Status
                  </th>
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Started
                  </th>
                  <th scope="col" className="py-2 pr-4 font-semibold">
                    Crafted
                  </th>
                  <th scope="col" className="py-2 font-semibold">
                    Failed
                  </th>
                </tr>
              </thead>
              <tbody>
                {runs.data.length === 0 ? (
                  <tr>
                    <td
                      colSpan={7}
                      className="py-4 text-center text-gray-500 dark:text-gray-400"
                    >
                      No runs found.
                    </td>
                  </tr>
                ) : (
                  runs.data.map((row) => (
                    <tr
                      key={row.id}
                      className="border-b border-gray-100 dark:border-gray-800"
                    >
                      <td className="py-2 pr-4">
                        {row.character?.name ?? '—'}
                      </td>
                      <td className="py-2 pr-4">{row.batch_type}</td>
                      <td className="py-2 pr-4">{row.disposition}</td>
                      <td className="py-2 pr-4 capitalize">
                        {humanizeStatus(row)}
                      </td>
                      <td className="py-2 pr-4">{row.started_at ?? '—'}</td>
                      <td className="py-2 pr-4">{row.crafted_count}</td>
                      <td className="py-2">{row.failed_count}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
          <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
            <span className="text-sm text-gray-600 dark:text-gray-300">
              Page {runs.current_page} of {runs.last_page}
            </span>
            <div className="flex gap-2">
              <button
                type="button"
                className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
                disabled={runs.current_page <= 1}
                onClick={() => setPage(runs.current_page - 1)}
              >
                Previous
              </button>
              <button
                type="button"
                className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
                disabled={runs.current_page >= runs.last_page}
                onClick={() => setPage(runs.current_page + 1)}
              >
                Next
              </button>
            </div>
          </div>
        </MonitorCard>
      </div>
      <MonitorCard>
        <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
          Batch Crafting Logs
        </h2>
        <div className="mb-3">
          <label className="text-sm font-medium">
            Severity
            <select
              className="ml-2 rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
              value={logSeverity}
              onChange={(e) => {
                updateLogSeverity(e.target.value);
              }}
            >
              <option value="">All</option>
              <option value="error">Error</option>
              <option value="warning">Warning</option>
              <option value="info">Info</option>
              <option value="debug">Debug</option>
            </select>
          </label>
        </div>
        {logs.data.length === 0 ? (
          <p className="text-sm text-gray-500 dark:text-gray-400">
            No log entries found.
          </p>
        ) : (
          <ul className="grid gap-3">
            {logs.data.map((entry, index) => (
              <li
                key={`${entry.detail_id}-${index}`}
                className="border-b border-gray-100 pb-2 text-xs dark:border-gray-800"
              >
                <div className="flex flex-wrap items-baseline gap-x-2">
                  <span className="text-gray-400 dark:text-gray-500">
                    {entry.timestamp ?? 'Unknown time'}
                  </span>
                  <span
                    className={`font-semibold uppercase ${levelColor(entry.severity)}`}
                  >
                    {entry.severity}
                  </span>
                </div>
                <p className="mt-1 text-gray-700 dark:text-gray-200">
                  {entry.message}
                </p>
              </li>
            ))}
          </ul>
        )}
        <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
          <span className="text-sm text-gray-600 dark:text-gray-300">
            Page {logs.current_page} of {logs.last_page}
          </span>
          <div className="flex gap-2">
            <button
              type="button"
              className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
              disabled={logs.current_page <= 1}
              onClick={() => setLogPage(logs.current_page - 1)}
            >
              Previous
            </button>
            <button
              type="button"
              className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
              disabled={logs.current_page >= logs.last_page}
              onClick={() => setLogPage(logs.current_page + 1)}
            >
              Next
            </button>
          </div>
        </div>
      </MonitorCard>
    </div>
  );
}
