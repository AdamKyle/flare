import React, { useState } from 'react';

import AdminPaginationControls from '../../../shared/components/admin-pagination-controls';
import MonitoringStatusChart from '../../components/monitoring-status-chart';
import useScrollToElement from '../../hooks/use-scroll-to-element';
import { ADMIN_MONITORING_CHART_COLORS } from '../../values/admin-monitoring-chart-colors';
import useFactionLoyaltyDashboard from '../hooks/use-faction-loyalty-dashboard';
import {
  LogDetailsProps,
  MonitorCardProps,
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

function LogDetails({ log }: LogDetailsProps) {
  const [open, setOpen] = useState(false);

  const fightLogs = log?.fight_logs ?? [];
  const craftingLogs = log?.crafting_logs ?? [];
  const hasLogs = fightLogs.length > 0 || craftingLogs.length > 0;

  if (!hasLogs) {
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
        {open
          ? 'Hide logs'
          : `Show logs (${fightLogs.length} fight, ${craftingLogs.length} craft)`}
      </button>
      {open && (
        <div className="mt-2 space-y-2">
          {fightLogs.length > 0 && (
            <details open>
              <summary className="cursor-pointer text-xs font-medium">
                Fight logs ({fightLogs.length})
              </summary>
              <pre className="mt-1 max-h-40 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-gray-800">
                {JSON.stringify(fightLogs, null, 2)}
              </pre>
            </details>
          )}
          {craftingLogs.length > 0 && (
            <details open>
              <summary className="cursor-pointer text-xs font-medium">
                Crafting logs ({craftingLogs.length})
              </summary>
              <pre className="mt-1 max-h-40 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-gray-800">
                {JSON.stringify(craftingLogs, null, 2)}
              </pre>
            </details>
          )}
        </div>
      )}
    </div>
  );
}

export default function FactionLoyaltyDashboard() {
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
  } = useFactionLoyaltyDashboard();

  const { scroll_to_element: scrollToElement } = useScrollToElement();
  const runsTableElementId = 'faction-loyalty-runs-table';

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
          Loading faction loyalty data…
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

      <div className="grid gap-3 sm:grid-cols-3">
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
        ].map(({ label, value }) => (
          <MonitorCard
            key={label}
            ariaLabel={`Filter runs by ${label}`}
            onClick={() => {
              if (label === 'Active') {
                applyTableFilter({ status: 'active' });
              } else if (label === 'Completed') {
                applyTableFilter({ status: 'completed' });
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
          title="Faction Loyalty Runs per Period"
          description="Run, active, and completed totals from faction loyalty automation data."
          points={chart}
          series={[
            {
              key: 'runs',
              label: 'Runs',
              color: ADMIN_MONITORING_CHART_COLORS.cosmic,
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
          ]}
        />
      </div>

      <MonitorCard>
        <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
          Currently Active
        </h2>
        {active.length === 0 ? (
          <p className="text-sm text-gray-600 dark:text-gray-300">
            No characters are currently in faction loyalty automation.
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
                    NPC
                  </th>
                  <th scope="col" className="p-2">
                    Last action
                  </th>
                  <th scope="col" className="p-2">
                    Started
                  </th>
                  <th scope="col" className="p-2">
                    Last fight
                  </th>
                  <th scope="col" className="p-2">
                    Bounty target
                  </th>
                  <th scope="col" className="p-2">
                    Failed bounty monster
                  </th>
                  <th scope="col" className="p-2">
                    Failed craft item
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
                    <td className="p-2">{runner.npc_name ?? '—'}</td>
                    <td className="p-2">{runner.last_action ?? '—'}</td>
                    <td className="p-2">{runner.started_at ?? '—'}</td>
                    <td className="p-2">{runner.last_fight_outcome ?? '—'}</td>
                    <td className="p-2">
                      {runner.last_fight_was_bounty_target === null
                        ? '—'
                        : runner.last_fight_was_bounty_target
                          ? 'Yes'
                          : 'No'}
                    </td>
                    <td className="p-2">
                      {runner.failed_bounty_monster_name ?? '—'}
                    </td>
                    <td className="p-2">
                      {runner.failed_crafting_item_name ?? '—'}
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
                    NPC
                  </th>
                  <th scope="col" className="p-2">
                    Last action
                  </th>
                  <th scope="col" className="p-2">
                    Started
                  </th>
                  <th scope="col" className="p-2">
                    Completed
                  </th>
                  <th scope="col" className="p-2">
                    Logs
                  </th>
                </tr>
              </thead>
              <tbody>
                {runs.data.map((run) => (
                  <tr className="border-t dark:border-gray-700" key={run.id}>
                    <td className="p-2">{run.character?.name ?? '—'}</td>
                    <td className="p-2">{run.npc_name ?? '—'}</td>
                    <td className="p-2">{run.last_automation_action ?? '—'}</td>
                    <td className="p-2">{run.started_at ?? '—'}</td>
                    <td className="p-2">{run.completed_at ?? 'Active'}</td>
                    <td className="p-2">
                      <LogDetails log={run.log} />
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
            label="faction loyalty runs"
            on_page_change={setPage}
          />
        </MonitorCard>
      </div>
    </div>
  );
}
