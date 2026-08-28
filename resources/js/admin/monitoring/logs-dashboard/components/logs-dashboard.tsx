import React, { useCallback, useMemo, useRef, useState } from 'react';

import SeverityBadge from './severity-badge';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import MonitoringStatusChart from '../../components/monitoring-status-chart';
import { ADMIN_MONITORING_CHART_COLORS } from '../../values/admin-monitoring-chart-colors';
import AdminPaginationControls from '../../../shared/components/admin-pagination-controls';
import LogsPollResponseDefinition from '../api/definitions/logs-poll-response-definition';
import { LogsDashboardMessages } from '../api/enums/logs-dashboard-messages';
import { useLogsApi } from '../api/hooks/use-logs-api';
import useLogDashboardData from '../hooks/use-log-dashboard-data';
import useLogFiles from '../hooks/use-log-files';
import useLogPolling from '../hooks/use-log-polling';
import {
  LogEntry,
  LogEntryDetail,
  SEVERITIES,
  SystemBugReport,
} from '../types/logs-dashboard';
import logEntriesPaginationAdapter from '../utils/log-entries-pagination-adapter';
import { LOG_FILTER_DEFAULTS } from '../values/log-filter-defaults';

export default function LogsDashboard() {
  const sidePeekEmitter = useSidePeekEmitter();
  const {
    fetchLogFiles,
    fetchLogEntries,
    fetchLogEntryDetail,
    pollLogs,
    fetchSystemBugs,
    fetchBugChart,
  } = useLogsApi();
  const {
    loading,
    error,
    files,
    selected_file: selectedFile,
    set_selected_file: setSelectedFile,
  } = useLogFiles(fetchLogFiles);

  const [filters, setFilters] = useState(LOG_FILTER_DEFAULTS);
  const [page, setPage] = useState(1);
  const [newEntries, setNewEntries] = useState<LogEntry[]>([]);
  const [bugRange, setBugRange] = useState(30);
  const [loadingDetailId, setLoadingDetailId] = useState<string | null>(null);
  const [detailError, setDetailError] = useState('');
  const [pollError, setPollError] = useState('');
  const tableRef = useRef<HTMLElement | null>(null);

  const {
    entries,
    summary,
    bugs,
    bug_chart: bugChart,
    data_error: dataError,
    load_data: loadData,
  } = useLogDashboardData({
    selected_file: selectedFile,
    filters,
    page,
    bug_range: bugRange,
    fetch_log_entries: fetchLogEntries,
    fetch_system_bugs: fetchSystemBugs,
    fetch_bug_chart: fetchBugChart,
  });

  const chartPoints = useMemo(
    () =>
      summary.chart.map((point) => ({
        period: point.period,
        entries: point.count,
      })),
    [summary.chart]
  );
  const displayedError = error || dataError || pollError;

  const setSeverityFilter = (severity: string) => {
    setFilters({ ...filters, severity });
    setPage(1);
  };

  const openLogEntrySidePeek = useCallback(
    async (entry: LogEntry) => {
      setDetailError('');
      setLoadingDetailId(entry.detail_id);

      try {
        const detail: LogEntryDetail = await fetchLogEntryDetail(
          entry.file_key,
          entry.detail_id
        );
        sidePeekEmitter.emit(
          SidePeek.SIDE_PEEK,
          SidePeekComponentRegistrationEnum.ADMIN_LOG_ENTRY,
          { title: 'Log Detail', is_open: true, entry: detail }
        );
      } catch {
        setDetailError(LogsDashboardMessages.LoadDetail);
      } finally {
        setLoadingDetailId(null);
      }
    },
    [fetchLogEntryDetail, sidePeekEmitter]
  );

  const openBugReportSidePeek = useCallback(
    (bug: SystemBugReport) => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.ADMIN_BUG_REPORT,
        {
          title: bug.title,
          is_open: true,
          bug,
        }
      );
    },
    [sidePeekEmitter]
  );

  const handlePoll = useCallback(
    (payload: LogsPollResponseDefinition) => {
      setNewEntries(payload.entries);
      void loadData();
    },
    [loadData]
  );

  const handlePollError = useCallback(() => {
    setPollError(LogsDashboardMessages.Poll);
  }, []);

  useLogPolling({
    selected_file: selectedFile,
    filters,
    poll_logs: pollLogs,
    on_poll: handlePoll,
    on_error: handlePollError,
  });

  const renderBugSeverity = (bug: SystemBugReport) => {
    if (!bug.severity) {
      return '-';
    }

    return <SeverityBadge severity={bug.severity} />;
  };

  return (
    <div className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
      {loading && (
        <p role="status" aria-live="polite">
          Loading log files...
        </p>
      )}
      {displayedError && (
        <p
          className="rounded border border-red-400 bg-red-50 p-3 text-red-800 dark:bg-red-950 dark:text-red-100"
          role="alert"
        >
          {displayedError}
        </p>
      )}

      <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-5 dark:border-gray-700 dark:bg-gray-900">
        <h2 className="mb-3 text-lg font-semibold">Log Files</h2>
        <div className="flex flex-wrap gap-2">
          {files.map((f) => (
            <button
              type="button"
              key={f.key}
              disabled={!f.exists}
              onClick={() => {
                setSelectedFile(f.key);
                setPage(1);
              }}
              className={[
                'rounded border px-3 py-1.5 text-sm transition-colors',
                selectedFile === f.key
                  ? 'border-indigo-500 bg-indigo-50 font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200'
                  : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-800',
                !f.exists ? 'cursor-not-allowed opacity-40' : '',
              ].join(' ')}
            >
              {f.label}
              {f.exists && (
                <span className="ml-1.5 text-xs text-gray-500 dark:text-gray-400">
                  {(f.size_bytes / 1024).toFixed(1)}KB
                </span>
              )}
              {!f.exists && <span className="ml-1.5 text-xs">(missing)</span>}
            </button>
          ))}
        </div>
      </section>

      {selectedFile && (
        <>
          <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <button
              type="button"
              className="rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm dark:border-gray-700 dark:bg-gray-900"
              onClick={() => setSeverityFilter('')}
            >
              <span className="text-sm text-gray-500">Total</span>
              <span className="mt-1 block text-2xl font-semibold">
                {summary.total}
              </span>
            </button>
            {Object.entries(summary.by_severity).map(([sev, cnt]) => (
              <button
                type="button"
                key={sev}
                className="rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm dark:border-gray-700 dark:bg-gray-900"
                onClick={() => setSeverityFilter(sev)}
              >
                <span className="text-sm text-gray-500">{sev}</span>
                <span className="mt-1 block text-2xl font-semibold">{cnt}</span>
              </button>
            ))}
          </section>

          {newEntries.length > 0 && (
            <section className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
              {newEntries.length} new log{' '}
              {newEntries.length === 1 ? 'entry' : 'entries'} read during the
              latest poll.
            </section>
          )}

          <section
            ref={tableRef}
            className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-5 dark:border-gray-700 dark:bg-gray-900"
          >
            <h2 className="mb-3 text-lg font-semibold">Recent Logs</h2>
            {detailError && (
              <p
                className="mb-3 text-sm text-rose-700 dark:text-rose-300"
                role="alert"
              >
                {detailError}
              </p>
            )}
            <div className="mb-3 flex flex-wrap gap-2">
              {[
                { label: '1d', days: 1 },
                { label: '7d', days: 7 },
                { label: '14d', days: 14 },
                { label: '30d', days: 30 },
                { label: '6m', days: 180 },
                { label: '1y', days: 365 },
              ].map(({ label, days }) => {
                const from = new Date();
                from.setDate(from.getDate() - days);
                const fromStr = from.toISOString().slice(0, 10);
                const toStr = new Date().toISOString().slice(0, 10);
                return (
                  <button
                    type="button"
                    key={label}
                    className="rounded border border-gray-300 px-3 py-1 text-xs hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-800"
                    onClick={() => {
                      setFilters({
                        ...filters,
                        date_from: fromStr,
                        date_to: toStr,
                      });
                      setPage(1);
                    }}
                  >
                    {label}
                  </button>
                );
              })}
              <button
                type="button"
                className="rounded border border-gray-300 px-3 py-1 text-xs hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-800"
                onClick={() => {
                  setFilters({
                    ...filters,
                    date_from: '',
                    date_to: '',
                  });
                  setPage(1);
                }}
              >
                All time
              </button>
            </div>
            <div className="mb-4 grid gap-3 sm:grid-cols-3">
              <label className="text-sm font-medium">
                Severity
                <select
                  className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                  value={filters.severity}
                  onChange={(e) => {
                    setFilters({
                      ...filters,
                      severity: e.target.value,
                    });
                    setPage(1);
                  }}
                >
                  <option value="">All severities</option>
                  {SEVERITIES.filter(Boolean).map((s) => (
                    <option key={s} value={s}>
                      {s.charAt(0).toUpperCase() + s.slice(1)}
                    </option>
                  ))}
                </select>
              </label>
              <label className="text-sm font-medium">
                Date from
                <input
                  className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                  type="date"
                  value={filters.date_from}
                  onChange={(e) => {
                    setFilters({
                      ...filters,
                      date_from: e.target.value,
                    });
                    setPage(1);
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
                    setFilters({
                      ...filters,
                      date_to: e.target.value,
                    });
                    setPage(1);
                  }}
                />
              </label>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[840px] text-left text-sm">
                <thead>
                  <tr className="border-b dark:border-gray-700">
                    <th scope="col" className="p-2 whitespace-nowrap">
                      Timestamp
                    </th>
                    <th scope="col" className="p-2">
                      Severity
                    </th>
                    <th scope="col" className="p-2">
                      Channel
                    </th>
                    <th scope="col" className="p-2">
                      Message
                    </th>
                    <th scope="col" className="p-2">
                      Exception
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {entries.data.map((entry, idx) => (
                    <tr
                      key={`${entry.timestamp ?? 'raw'}-${idx}`}
                      className="border-t align-top dark:border-gray-700"
                    >
                      <td className="p-2 text-xs whitespace-nowrap text-gray-500">
                        {entry.timestamp ?? '-'}
                      </td>
                      <td className="p-2">
                        <SeverityBadge severity={entry.severity} />
                      </td>
                      <td className="p-2 text-xs">{entry.channel ?? '-'}</td>
                      <td className="p-2 text-sm">
                        <button
                          type="button"
                          className="text-left underline decoration-dotted underline-offset-2 disabled:cursor-wait disabled:opacity-60"
                          disabled={loadingDetailId === entry.detail_id}
                          aria-label={`View log detail: ${entry.message}`}
                          onClick={() => void openLogEntrySidePeek(entry)}
                        >
                          {loadingDetailId === entry.detail_id
                            ? 'Loading detail… '
                            : ''}
                          {entry.message}
                        </button>
                      </td>
                      <td className="p-2 text-xs">
                        {entry.exception_class ?? '-'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {entries.data.length === 0 && (
                <p className="p-4 text-center text-gray-600 dark:text-gray-300">
                  No log entries match the current filters.
                </p>
              )}
            </div>
            <AdminPaginationControls
              response={logEntriesPaginationAdapter(entries)}
              label="log entries"
              on_page_change={setPage}
            />
          </section>

          <MonitoringStatusChart
            title="Log Volume"
            description="Parsed log entries by day for the selected channel and filters."
            points={chartPoints}
            series={[
              {
                key: 'entries',
                label: 'Entries',
                color: ADMIN_MONITORING_CHART_COLORS.indigoStrong,
              },
            ]}
          />

          <div className="flex flex-wrap items-center justify-between gap-2">
            <h2 className="text-lg font-semibold">System Error Occurrences</h2>
            <div className="flex flex-wrap gap-2">
              {[7, 14, 30, 60, 120].map((days) => (
                <button
                  type="button"
                  key={days}
                  className={[
                    'rounded border px-2 py-1 text-xs',
                    bugRange === days
                      ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200'
                      : 'border-gray-300 dark:border-gray-600',
                  ].join(' ')}
                  onClick={() => setBugRange(days)}
                >
                  {days}d
                </button>
              ))}
            </div>
          </div>
          <MonitoringStatusChart
            title="Bug Occurrences"
            description="System error occurrences grouped by day."
            points={bugChart}
            series={[
              {
                key: 'occurrences',
                label: 'Occurrences',
                color: ADMIN_MONITORING_CHART_COLORS.roseStrong,
              },
            ]}
          />
          <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-5 dark:border-gray-700 dark:bg-gray-900">
            <div className="mb-3">
              <h2 className="text-lg font-semibold">Grouped Bugs</h2>
            </div>
            <div className="mt-4 overflow-x-auto">
              <table className="w-full min-w-[760px] text-left text-sm">
                <thead>
                  <tr className="border-b dark:border-gray-700">
                    <th scope="col" className="p-2">
                      Bug
                    </th>
                    <th scope="col" className="p-2">
                      Status
                    </th>
                    <th scope="col" className="p-2">
                      Severity
                    </th>
                    <th scope="col" className="p-2">
                      Occurrences
                    </th>
                    <th scope="col" className="p-2">
                      Last Seen
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {bugs.map((bug) => (
                    <tr key={bug.id} className="border-t dark:border-gray-700">
                      <td className="p-2">
                        <button
                          type="button"
                          className="text-left underline decoration-dotted underline-offset-2"
                          onClick={() => openBugReportSidePeek(bug)}
                          aria-label={`View bug report: ${bug.title}`}
                        >
                          {bug.title}
                        </button>
                      </td>
                      <td className="p-2">{bug.status}</td>
                      <td className="p-2">{renderBugSeverity(bug)}</td>
                      <td className="p-2">{bug.occurrence_count}</td>
                      <td className="p-2">{bug.last_seen_at ?? '-'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {bugs.length === 0 && (
                <p className="p-4 text-center text-gray-600 dark:text-gray-300">
                  No system errors have been ingested.
                </p>
              )}
            </div>
          </section>
        </>
      )}
    </div>
  );
}
