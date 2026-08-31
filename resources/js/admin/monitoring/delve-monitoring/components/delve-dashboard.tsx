import React, { useState } from 'react';

import AdminPaginationControls from '../../../shared/components/admin-pagination-controls';
import MonitoringStatusChart from '../../components/monitoring-status-chart';
import useScrollToElement from '../../hooks/use-scroll-to-element';
import { ADMIN_MONITORING_CHART_COLORS } from '../../values/admin-monitoring-chart-colors';
import useDelveDashboard from '../hooks/use-delve-dashboard';
import {
  MonitorCardProps,
  RunLogDetailsProps,
} from '../types/dashboard-component-props';

function MonitorCard({ children, onClick, ariaLabel }: MonitorCardProps) {
  const classes =
    'rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-5 ' +
    'dark:border-gray-700 dark:bg-gray-900';

  if (onClick) {
    return (
      <button
        type="button"
        className={`${classes} cursor-pointer text-left transition-colors hover:border-blue-400 focus:ring-2 focus:ring-blue-400 focus:outline-none dark:hover:border-blue-500`}
        onClick={onClick}
        aria-label={ariaLabel}
      >
        {children}
      </button>
    );
  }

  return <section className={classes}>{children}</section>;
}

function RunLogDetails({ logs }: RunLogDetailsProps) {
  const [open, setOpen] = useState(false);
  const [page, setPage] = useState(1);
  const totalPages = Math.max(1, Math.ceil(logs.length / 10));
  const rows = logs.slice((page - 1) * 10, page * 10);

  if (logs.length === 0) {
    return <span className="text-xs text-gray-400">No logs</span>;
  }

  return (
    <div>
      <button
        type="button"
        className="text-xs text-blue-500 underline"
        onClick={() => setOpen(!open)}
        aria-expanded={open}
      >
        {open ? 'Hide logs' : `Show ${logs.length} log(s)`}
      </button>
      {open && (
        <div className="mt-2 overflow-x-auto rounded border border-gray-200 dark:border-gray-600">
          <table className="w-full text-left text-xs">
            <thead>
              <tr className="border-b bg-gray-50 dark:border-gray-600 dark:bg-gray-800">
                <th className="p-1">Outcome</th>
                <th className="p-1">Pack size</th>
                <th className="p-1">Enemy strength</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((log) => (
                <tr key={log.id} className="border-t dark:border-gray-700">
                  <td className="p-1">{log.outcome}</td>
                  <td className="p-1">{log.pack_size}</td>
                  <td className="p-1">
                    {log.increased_enemy_strength !== null
                      ? `${Math.round((log.increased_enemy_strength ?? 0) * 100)}%`
                      : '—'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {totalPages > 1 && (
            <div className="flex flex-wrap items-center justify-between gap-2 border-t p-2 text-xs dark:border-gray-700">
              <span className="text-gray-600 dark:text-gray-300">
                Page {page} of {totalPages}
              </span>
              <div className="flex gap-2">
                <button
                  type="button"
                  className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                  disabled={page <= 1}
                  onClick={() => setPage((currentPage) => currentPage - 1)}
                >
                  Previous
                </button>
                <button
                  type="button"
                  className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                  disabled={page >= totalPages}
                  onClick={() => setPage((currentPage) => currentPage + 1)}
                >
                  Next
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default function DelveDashboard() {
  const {
    loading,
    error,
    active,
    runs,
    summary,
    chart,
    filters,
    update_filters: updateFilters,
    apply_table_filter: applyTableFilterData,
    set_page: setPage,
    days,
    set_days: setDays,
    day_options: DAY_OPTIONS,
  } = useDelveDashboard();

  const { scroll_to_element: scrollToElement } = useScrollToElement();
  const runsTableElementId = 'delve-runs-table';

  const applyTableFilter = (
    nextFilters: Parameters<typeof applyTableFilterData>[0]
  ) => {
    applyTableFilterData(nextFilters);
    scrollToElement(runsTableElementId);
  };

  return (
    <div className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
      {loading && (
        <p role="status" aria-live="polite">
          Loading delve monitoring data…
        </p>
      )}
      {error && (
        <p
          className="rounded border border-red-400 bg-red-50 p-3 text-red-800 dark:bg-red-950 dark:text-red-100"
          role="alert"
        >
          {error}
        </p>
      )}

      <div className="grid gap-3 sm:grid-cols-3 xl:grid-cols-6">
        <MonitorCard>
          <div className="text-sm text-gray-600 dark:text-gray-300">
            Total Runs
          </div>
          <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
            {summary.total_runs}
          </div>
        </MonitorCard>
        {[
          { label: 'Active', value: summary.active },
          { label: 'Completed', value: summary.completed },
          { label: 'Survived', value: summary.total_survived },
          { label: 'Died', value: summary.total_died },
          { label: 'Timeout', value: summary.total_timeout },
        ].map(({ label, value }) => (
          <MonitorCard
            key={label}
            ariaLabel={`Filter runs by ${label}`}
            onClick={() => {
              if (label === 'Active') {
                applyTableFilter({ status: 'active' });
              } else if (label === 'Completed') {
                applyTableFilter({ status: 'completed' });
              } else if (label === 'Survived') {
                applyTableFilter({ outcome: 'survived' });
              } else if (label === 'Died') {
                applyTableFilter({ outcome: 'died' });
              } else if (label === 'Timeout') {
                applyTableFilter({ outcome: 'timeout' });
              }
            }}
          >
            <div className="text-sm text-gray-600 dark:text-gray-300">
              {label}
            </div>
            <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
              {value}
            </div>
          </MonitorCard>
        ))}
      </div>

      <div>
        <label className="mb-3 block text-sm font-medium">
          Period
          <select
            className="ml-2 rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
            value={days}
            onChange={(e) => setDays(e.target.value)}
            aria-label="Select period"
          >
            {DAY_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </label>
        <MonitoringStatusChart
          title="Delve Runs per Period"
          description="Run, status, and outcome totals from retained Delve data."
          points={chart}
          series={[
            {
              key: 'runs',
              label: 'Runs',
              color: ADMIN_MONITORING_CHART_COLORS.mangoTango,
            },
            {
              key: 'active',
              label: 'Active',
              color: ADMIN_MONITORING_CHART_COLORS.danube,
            },
            {
              key: 'completed',
              label: 'Completed',
              color: ADMIN_MONITORING_CHART_COLORS.emerald,
            },
            {
              key: 'survived',
              label: 'Survived',
              color: ADMIN_MONITORING_CHART_COLORS.emeraldStrong,
            },
            {
              key: 'died',
              label: 'Died',
              color: ADMIN_MONITORING_CHART_COLORS.rose,
              dash: '6,3',
            },
            {
              key: 'timeout',
              label: 'Timeout',
              color: ADMIN_MONITORING_CHART_COLORS.mangoTango,
              dash: '2,2',
            },
          ]}
        />
      </div>

      <MonitorCard>
        <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
          Currently Active
        </h2>
        {active.length === 0 ? (
          <p className="text-sm text-gray-600 dark:text-gray-300">
            No characters are currently in a delve.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[700px] text-left text-sm">
              <thead>
                <tr className="border-b dark:border-gray-700">
                  <th scope="col" className="p-2">
                    Character
                  </th>
                  <th scope="col" className="p-2">
                    Enemy strength
                  </th>
                  <th scope="col" className="p-2">
                    Increase %
                  </th>
                  <th scope="col" className="p-2">
                    Started
                  </th>
                  <th scope="col" className="p-2">
                    Encounters
                  </th>
                  <th scope="col" className="p-2">
                    Avg pack
                  </th>
                  <th scope="col" className="p-2">
                    Outcomes
                  </th>
                </tr>
              </thead>
              <tbody>
                {active.map((runner) => (
                  <tr
                    className="border-t dark:border-gray-700"
                    key={runner.character_id}
                  >
                    <td className="p-2 font-medium">
                      {runner.character_name ?? '—'}
                    </td>
                    <td className="p-2">
                      {runner.increase_enemy_strength ?? '—'}
                    </td>
                    <td className="p-2">
                      {runner.increase_percentage !== null
                        ? `${runner.increase_percentage}%`
                        : '—'}
                    </td>
                    <td className="p-2">{runner.started_at ?? '—'}</td>
                    <td className="p-2">{runner.total_encounters}</td>
                    <td className="p-2">
                      {runner.avg_pack_size !== null
                        ? runner.avg_pack_size
                        : '—'}
                    </td>
                    <td className="p-2 text-xs">
                      {runner.outcome_counts ? (
                        <span>
                          ✓{runner.outcome_counts.survived} ✗
                          {runner.outcome_counts.died} ⏱
                          {runner.outcome_counts.timeout}
                        </span>
                      ) : (
                        '—'
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </MonitorCard>

      <div id={runsTableElementId}>
        <MonitorCard>
          <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
            Recent Runs
          </h2>
          <div className="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <label className="text-sm font-medium">
              Character name
              <input
                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
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
                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
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
                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
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
          </div>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[700px] text-left text-sm">
              <thead>
                <tr className="border-b dark:border-gray-700">
                  <th scope="col" className="p-2">
                    Character
                  </th>
                  <th scope="col" className="p-2">
                    Enemy strength
                  </th>
                  <th scope="col" className="p-2">
                    Started
                  </th>
                  <th scope="col" className="p-2">
                    Completed
                  </th>
                  <th scope="col" className="p-2">
                    Run logs
                  </th>
                </tr>
              </thead>
              <tbody>
                {runs.data.map((run) => (
                  <tr className="border-t dark:border-gray-700" key={run.id}>
                    <td className="p-2">{run.character?.name ?? '—'}</td>
                    <td className="p-2">
                      {run.increase_enemy_strength ?? '—'}
                    </td>
                    <td className="p-2">{run.started_at ?? '—'}</td>
                    <td className="p-2">{run.completed_at ?? 'Active'}</td>
                    <td className="p-2">
                      <RunLogDetails logs={run.delve_logs ?? []} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {runs.data.length === 0 && (
              <p className="p-4 text-center text-gray-600 dark:text-gray-300">
                No runs found.
              </p>
            )}
          </div>
          <AdminPaginationControls
            response={runs}
            label="delve runs"
            on_page_change={setPage}
          />
        </MonitorCard>
      </div>
    </div>
  );
}
