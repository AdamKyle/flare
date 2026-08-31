import React from 'react';

import ActiveExplorersTable from './active-explorers-table';
import ExplorationLogsTable from './exploration-logs-table';
import MonitoringCard from './monitoring-card';
import MonitoringStatusChart from '../../components/monitoring-status-chart';
import useScrollToElement from '../../hooks/use-scroll-to-element';
import { ADMIN_MONITORING_CHART_COLORS } from '../../values/admin-monitoring-chart-colors';
import useExplorationDashboard from '../hooks/use-exploration-dashboard';

const LOGS_TABLE_ELEMENT_ID = 'exploration-logs-table';

export default function ExplorationDashboard() {
  const {
    loading,
    error,
    active_explorers: activeExplorers,
    logs,
    summary,
    chart,
    filters,
    update_filters: updateFilters,
    apply_table_filter: applyTableFilterData,
    set_log_page: setLogPage,
    days,
    set_days: setDays,
    day_options: DAY_OPTIONS,
  } = useExplorationDashboard();

  const { scroll_to_element: scrollToElement } = useScrollToElement();

  const applyTableFilter = (
    nextFilters: Parameters<typeof applyTableFilterData>[0]
  ) => {
    applyTableFilterData(nextFilters);
    scrollToElement(LOGS_TABLE_ELEMENT_ID);
  };

  return (
    <div className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
      {loading && (
        <p role="status" aria-live="polite">
          Loading exploration data…
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

      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        {[
          { label: 'Total Runs', value: summary.total_runs },
          {
            label: 'Stopped by Player',
            value: summary.stopped_by_player,
          },
          {
            label: 'Total Kills',
            value: summary.total_kills.toLocaleString(),
          },
          {
            label: 'Total XP',
            value: summary.total_xp_gained.toLocaleString(),
          },
          {
            label: 'Skill XP',
            value: summary.total_skill_xp_gained.toLocaleString(),
          },
        ].map(({ label, value }) => {
          const isFilterCard = label === 'Stopped by Player';

          return (
            <MonitoringCard
              key={label}
              onClick={
                isFilterCard
                  ? () => applyTableFilter({ stopped_by_player: true })
                  : undefined
              }
              ariaLabel={
                isFilterCard ? 'Filter runs stopped by player' : undefined
              }
            >
              <div className="text-sm text-gray-600 dark:text-gray-300">
                {label}
              </div>
              <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {value}
              </div>
            </MonitoringCard>
          );
        })}
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
          title="Exploration Metrics per Period"
          description="Run, kill, XP, skill XP, active, and completed totals from exploration logs."
          points={chart}
          series={[
            {
              key: 'runs',
              label: 'Runs',
              color: ADMIN_MONITORING_CHART_COLORS.emerald,
            },
            {
              key: 'kills',
              label: 'Kills',
              color: ADMIN_MONITORING_CHART_COLORS.emeraldStrong,
            },
            {
              key: 'xp',
              label: 'XP',
              color: ADMIN_MONITORING_CHART_COLORS.danube,
            },
            {
              key: 'skill_xp',
              label: 'Skill XP',
              color: ADMIN_MONITORING_CHART_COLORS.cosmic,
            },
            {
              key: 'active',
              label: 'Active',
              color: ADMIN_MONITORING_CHART_COLORS.mangoTango,
              dash: '2,2',
            },
            {
              key: 'completed',
              label: 'Completed',
              color: ADMIN_MONITORING_CHART_COLORS.glacier,
            },
          ]}
        />
      </div>

      <ActiveExplorersTable explorers={activeExplorers} />

      <ExplorationLogsTable
        logs={logs}
        filters={filters}
        onFiltersChange={updateFilters}
        onPageChange={setLogPage}
      />
    </div>
  );
}
