import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import React, { useCallback, useEffect, useState } from 'react';

import ActiveExplorersTable from './active-explorers-table';
import ExplorationLogsTable from './exploration-logs-table';
import MonitoringCard from './monitoring-card';
import MonitoringStatusChart from '../../monitoring/components/monitoring-status-chart';
import { useExplorationApi } from '../ajax/exploration-api';
import useExplorationLiveRefresh from '../hooks/use-exploration-live-refresh';
import {
  ActiveExplorer,
  ExplorationChartPoint,
  ExplorationFilters,
  ExplorationLogRow,
  ExplorationSummary,
} from '../types/exploration-monitoring';
import { DAY_OPTIONS } from '../values/filter-options';

const emptyPaginatedResponse = <T,>(): PaginatedApiResponseDefinition<T[]> =>
  ({
    data: [],
    meta: {
      can_load_more: false,
      pagination: {
        count: 0,
        [`current${'_'}page`]: 1,
        links: { next: '', prev: '' },
        per_page: 10,
        total: 0,
        total_pages: 1,
      },
    },
  }) as PaginatedApiResponseDefinition<T[]>;

const emptySummary: ExplorationSummary = {
  total_runs: 0,
  stopped_by_player: 0,
  total_kills: 0,
  total_xp_gained: 0,
  total_skill_xp_gained: 0,
};

const defaultFilters: ExplorationFilters = {
  character_name: '',
  stopped_reason: '',
  stopped_by_player: false,
  date_from: '',
  date_to: '',
  days: '7',
};

export default function ExplorationDashboard() {
  const {
    fetchExplorationActive,
    fetchExplorationLogs,
    fetchExplorationSummary,
    fetchExplorationChart,
  } = useExplorationApi();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeExplorers, setActiveExplorers] = useState<ActiveExplorer[]>([]);
  const [logs, setLogs] = useState<
    PaginatedApiResponseDefinition<ExplorationLogRow[]>
  >(emptyPaginatedResponse());
  const [summary, setSummary] = useState<ExplorationSummary>(emptySummary);
  const [chart, setChart] = useState<ExplorationChartPoint[]>([]);
  const [filters, setFilters] = useState<ExplorationFilters>(defaultFilters);
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

  const applyTableFilter = (nextFilters: Partial<ExplorationFilters>) => {
    setFilters({ ...defaultFilters, ...nextFilters });
    setLogPage(1);
    window.setTimeout(() => {
      document
        .getElementById('exploration-logs-table')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 0);
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
        ].map(({ label, value }) => (
          <button
            key={label}
            type="button"
            className="text-left"
            onClick={() => {
              if (label === 'Stopped by Player') {
                applyTableFilter({ stopped_by_player: true });
              }
            }}
          >
            <MonitoringCard>
              <div className="text-sm text-gray-600 dark:text-gray-300">
                {label}
              </div>
              <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {value}
              </div>
            </MonitoringCard>
          </button>
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
          title="Exploration Metrics per Period"
          description="Run, kill, XP, skill XP, active, and completed totals from exploration logs."
          points={chart}
          series={[
            { key: 'runs', label: 'Runs', color: '#22c55e' },
            { key: 'kills', label: 'Kills', color: '#16a34a' },
            { key: 'xp', label: 'XP', color: '#3b82f6' },
            {
              key: 'skill_xp',
              label: 'Skill XP',
              color: '#a855f7',
            },
            {
              key: 'active',
              label: 'Active',
              color: '#f59e0b',
              dash: '2,2',
            },
            {
              key: 'completed',
              label: 'Completed',
              color: '#14b8a6',
            },
          ]}
        />
      </div>

      <ActiveExplorersTable explorers={activeExplorers} />

      <ExplorationLogsTable
        logs={logs}
        filters={filters}
        onFiltersChange={(nextFilters) => {
          setFilters(nextFilters);
          setLogPage(1);
        }}
        onPageChange={setLogPage}
      />
    </div>
  );
}
