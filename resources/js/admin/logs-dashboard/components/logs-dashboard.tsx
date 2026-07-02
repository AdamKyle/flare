import React from "react";
import MonitoringStatusChart from "../../monitoring/components/monitoring-status-chart";
import {
    fetchBugChart,
    fetchLogEntries,
    fetchLogFiles,
    fetchLogSummary,
    fetchSystemBugs,
    pollLogs,
} from "../ajax/logs-api";
import {
    LogEntriesPage,
    LogEntry,
    LogFileInfo,
    LogFilters,
    LogSummary,
    SEVERITIES,
    SystemBugReport,
} from "../types/logs-dashboard";
import LogsDashboardState from "../types/logs-dashboard-state";
import BugSidePeek from "./bug-side-peek";
import LogSidePeek from "./log-side-peek";
import PaginationControls from "./pagination-controls";
import SeverityBadge from "./severity-badge";

const defaultFilters: LogFilters = {
    severity: "",
    date_from: "",
    date_to: "",
};

const emptyPage = (): LogEntriesPage => ({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
});

export default class LogsDashboard extends React.Component<
    Record<string, never>,
    LogsDashboardState
> {
    private pollInterval?: number;

    private tableRef: React.RefObject<HTMLElement>;

    public constructor(props: Record<string, never>) {
        super(props);

        this.tableRef = React.createRef();

        this.state = {
            loading: true,
            error: "",
            files: [],
            selectedFile: "",
            filters: defaultFilters,
            page: 1,
            entries: emptyPage(),
            summary: {
                total: 0,
                by_severity: {},
                chart: [],
            },
            newEntries: [],
            bugs: [],
            bugChart: [],
            bugRange: 30,
            selectedEntry: null,
            selectedBug: null,
        };
    }

    componentDidMount() {
        void this.loadFiles();
    }

    componentWillUnmount() {
        this.clearPollInterval();
    }

    async loadFiles() {
        try {
            const files = await fetchLogFiles();
            const first = files.find((file: LogFileInfo) => file.exists);

            this.setState(
                {
                    files,
                    selectedFile: first?.key ?? this.state.selectedFile,
                },
                () => {
                    if (this.state.selectedFile !== "") {
                        void this.loadData();
                        void this.pollOnce();
                        this.startPolling();
                    }
                },
            );
        } catch {
            this.setState({
                error: "Could not load log file list.",
            });
        } finally {
            this.setState({
                loading: false,
            });
        }
    }

    async loadData() {
        if (!this.state.selectedFile) {
            return;
        }

        this.setState({
            error: "",
        });

        try {
            const [entriesData, summaryData, bugData, bugChartData] =
                await Promise.all([
                    fetchLogEntries(
                        this.state.selectedFile,
                        this.state.filters,
                        this.state.page,
                    ),
                    fetchLogSummary(
                        this.state.selectedFile,
                        this.state.filters,
                    ),
                    fetchSystemBugs(),
                    fetchBugChart(this.state.bugRange),
                ]);

            this.setState({
                entries: entriesData,
                summary: summaryData,
                bugs: bugData,
                bugChart: bugChartData,
            });
        } catch {
            this.setState({
                error: "Could not load log entries.",
            });
        }
    }

    async pollOnce() {
        if (!this.state.selectedFile) {
            return;
        }

        try {
            const payload = await pollLogs(
                this.state.selectedFile,
                this.state.filters,
            );

            this.setState({
                newEntries: payload.entries,
                summary: payload.summary,
                files: payload.files,
                bugs: payload.bugs,
                bugChart: payload.bug_chart,
            });
        } catch {
            // Preserve the previous polling behavior, which ignored the first poll failure.
        }
    }

    startPolling() {
        this.clearPollInterval();

        if (!this.state.selectedFile) {
            return;
        }

        this.pollInterval = window.setInterval(() => {
            pollLogs(this.state.selectedFile, this.state.filters)
                .then((payload) => {
                    this.setState({
                        newEntries: payload.entries,
                        summary: payload.summary,
                        files: payload.files,
                        bugs: payload.bugs,
                        bugChart: payload.bug_chart,
                    });
                    void this.loadData();
                })
                .catch(() => {
                    this.setState({
                        error: "Could not poll log entries.",
                    });
                });
        }, 60000);
    }

    clearPollInterval() {
        if (this.pollInterval !== undefined) {
            window.clearInterval(this.pollInterval);
            this.pollInterval = undefined;
        }
    }

    chartPoints() {
        return this.state.summary.chart.map((point) => ({
            period: point.period,
            entries: point.count,
        }));
    }

    setSeverityFilter(severity: string) {
        this.setFilters({
            ...this.state.filters,
            severity,
        });
    }

    setSelectedFile(selectedFile: string) {
        this.setState(
            {
                selectedFile,
                page: 1,
            },
            () => {
                void this.loadData();
                void this.pollOnce();
                this.startPolling();
            },
        );
    }

    setFilters(filters: LogFilters) {
        this.setState(
            {
                filters,
                page: 1,
            },
            () => {
                void this.loadData();
                this.startPolling();
            },
        );
    }

    setPage(page: number) {
        this.setState(
            {
                page,
            },
            () => {
                void this.loadData();
                this.startPolling();
            },
        );
    }

    setBugRange(bugRange: number) {
        this.setState(
            {
                bugRange,
            },
            () => void this.loadData(),
        );
    }

    selectEntry(entry: LogEntry) {
        this.setState({
            selectedEntry: entry,
            selectedBug: null,
        });
    }

    selectBug(bug: SystemBugReport) {
        this.setState({
            selectedBug: bug,
            selectedEntry: null,
        });
    }

    closeEntry() {
        this.setState({
            selectedEntry: null,
        });
    }

    closeBug() {
        this.setState({
            selectedBug: null,
        });
    }

    render() {
        const {
            bugChart,
            bugRange,
            bugs,
            entries,
            error,
            files,
            filters,
            loading,
            newEntries,
            selectedBug,
            selectedEntry,
            selectedFile,
            summary,
        } = this.state;

        return (
            <div className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
                {loading && (
                    <p role="status" aria-live="polite">
                        Loading log files...
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

                <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
                    <h2 className="mb-3 text-lg font-semibold">Log Files</h2>
                    <div className="grid gap-3 lg:grid-cols-[minmax(0,20rem)_1fr] lg:items-start">
                        <label className="text-sm font-medium">
                            Log file
                            <select
                                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                                value={selectedFile}
                                onChange={(event) => {
                                    this.setSelectedFile(event.target.value);
                                }}
                            >
                                {files
                                    .filter((file) => file.exists)
                                    .map((file) => (
                                        <option key={file.key} value={file.key}>
                                            {file.label}
                                        </option>
                                    ))}
                            </select>
                        </label>
                        <ul className="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            {files.map((file) => (
                                <li
                                    key={file.key}
                                    className={[
                                        "rounded border px-3 py-2 text-sm",
                                        selectedFile === file.key
                                            ? "border-indigo-500 bg-indigo-50 font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200"
                                            : "border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800",
                                        !file.exists ? "opacity-60" : "",
                                    ].join(" ")}
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="truncate">
                                            {file.label}
                                        </span>
                                        <span className="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                                            {file.exists
                                                ? `${(file.size_bytes / 1024).toFixed(1)}KB`
                                                : "missing"}
                                        </span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>

                {selectedFile && (
                    <>
                        <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <button
                                className="rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm dark:border-gray-700 dark:bg-gray-900"
                                onClick={() => this.setSeverityFilter("")}
                            >
                                <span className="text-sm text-gray-500">
                                    Total
                                </span>
                                <span className="mt-1 block text-2xl font-semibold">
                                    {summary.total}
                                </span>
                            </button>
                            {Object.entries(summary.by_severity).map(
                                ([sev, cnt]) => (
                                    <button
                                        key={sev}
                                        className="rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm dark:border-gray-700 dark:bg-gray-900"
                                        onClick={() =>
                                            this.setSeverityFilter(sev)
                                        }
                                    >
                                        <span className="text-sm text-gray-500">
                                            {sev}
                                        </span>
                                        <span className="mt-1 block text-2xl font-semibold">
                                            {cnt}
                                        </span>
                                    </button>
                                ),
                            )}
                        </section>

                        {newEntries.length > 0 && (
                            <section className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
                                {newEntries.length} new log{" "}
                                {newEntries.length === 1 ? "entry" : "entries"}{" "}
                                read during the latest poll.
                            </section>
                        )}

                        <section
                            ref={this.tableRef}
                            className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5"
                        >
                            <h2 className="mb-3 text-lg font-semibold">
                                Recent Logs
                            </h2>
                            <div className="mb-3 flex flex-wrap gap-2">
                                {[
                                    { label: "1d", days: 1 },
                                    { label: "7d", days: 7 },
                                    { label: "14d", days: 14 },
                                    { label: "30d", days: 30 },
                                    { label: "6m", days: 180 },
                                    { label: "1y", days: 365 },
                                ].map(({ label, days }) => {
                                    const from = new Date();
                                    from.setDate(from.getDate() - days);
                                    const fromStr = from
                                        .toISOString()
                                        .slice(0, 10);
                                    const toStr = new Date()
                                        .toISOString()
                                        .slice(0, 10);
                                    return (
                                        <button
                                            key={label}
                                            className="rounded border border-gray-300 px-3 py-1 text-xs hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-800"
                                            onClick={() => {
                                                this.setFilters({
                                                    ...filters,
                                                    date_from: fromStr,
                                                    date_to: toStr,
                                                });
                                            }}
                                        >
                                            {label}
                                        </button>
                                    );
                                })}
                                <button
                                    className="rounded border border-gray-300 px-3 py-1 text-xs hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-800"
                                    onClick={() => {
                                        this.setFilters({
                                            ...filters,
                                            date_from: "",
                                            date_to: "",
                                        });
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
                                            this.setFilters({
                                                ...filters,
                                                severity: e.target.value,
                                            });
                                        }}
                                    >
                                        <option value="">All severities</option>
                                        {SEVERITIES.filter(Boolean).map((s) => (
                                            <option key={s} value={s}>
                                                {s.charAt(0).toUpperCase() +
                                                    s.slice(1)}
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
                                            this.setFilters({
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
                                            this.setFilters({
                                                ...filters,
                                                date_to: e.target.value,
                                            });
                                        }}
                                    />
                                </label>
                            </div>
                            {entries.data.length === 0 && (
                                <p className="p-4 text-center text-gray-600 dark:text-gray-300">
                                    No log entries match the current filters.
                                </p>
                            )}
                            <div className="grid gap-3 md:hidden">
                                {entries.data.map((entry, idx) => (
                                    <button
                                        key={`${entry.timestamp ?? "raw"}-${idx}`}
                                        type="button"
                                        className="rounded border border-gray-200 p-3 text-left text-sm dark:border-gray-700"
                                        onClick={() => {
                                            this.selectEntry(entry);
                                        }}
                                    >
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <span className="text-xs text-gray-500">
                                                {entry.timestamp ?? "-"}
                                            </span>
                                            <SeverityBadge
                                                severity={entry.severity}
                                            />
                                        </div>
                                        <dl className="mt-2 grid gap-2">
                                            <div>
                                                <dt className="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                    Channel
                                                </dt>
                                                <dd className="break-words">
                                                    {entry.channel ?? "-"}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                    Message
                                                </dt>
                                                <dd className="break-words">
                                                    {entry.message}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                    Exception
                                                </dt>
                                                <dd className="break-words">
                                                    {entry.exception_class ??
                                                        "-"}
                                                </dd>
                                            </div>
                                        </dl>
                                    </button>
                                ))}
                            </div>
                            <div className="hidden overflow-x-auto md:block">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b dark:border-gray-700">
                                            <th
                                                scope="col"
                                                className="p-2 whitespace-nowrap"
                                            >
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
                                                key={`${entry.timestamp ?? "raw"}-${idx}`}
                                                className="cursor-pointer border-t align-top hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                                                role="button"
                                                tabIndex={0}
                                                onClick={() => {
                                                    this.selectEntry(entry);
                                                }}
                                                onKeyDown={(event) => {
                                                    if (
                                                        event.key === "Enter" ||
                                                        event.key === " "
                                                    ) {
                                                        event.preventDefault();
                                                        this.selectEntry(entry);
                                                    }
                                                }}
                                            >
                                                <td className="p-2 text-xs text-gray-500 whitespace-nowrap">
                                                    {entry.timestamp ?? "-"}
                                                </td>
                                                <td className="p-2">
                                                    <SeverityBadge
                                                        severity={
                                                            entry.severity
                                                        }
                                                    />
                                                </td>
                                                <td className="p-2 text-xs">
                                                    {entry.channel ?? "-"}
                                                </td>
                                                <td className="p-2 text-sm">
                                                    {entry.message}
                                                </td>
                                                <td className="p-2 text-xs">
                                                    {entry.exception_class ??
                                                        "-"}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <PaginationControls
                                currentPage={entries.current_page}
                                lastPage={entries.last_page}
                                onPageChange={(page: number) =>
                                    this.setPage(page)
                                }
                            />
                        </section>

                        <MonitoringStatusChart
                            title="Log Volume"
                            description="Parsed log entries by day for the selected channel and filters."
                            points={this.chartPoints()}
                            series={[
                                {
                                    key: "entries",
                                    label: "Entries",
                                    color: "#4f46e5",
                                },
                            ]}
                        />

                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <h2 className="text-lg font-semibold">
                                System Error Occurrences
                            </h2>
                            <div className="flex flex-wrap gap-2">
                                {[7, 14, 30, 60, 120].map((days) => (
                                    <button
                                        key={days}
                                        className={[
                                            "rounded border px-2 py-1 text-xs",
                                            bugRange === days
                                                ? "border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200"
                                                : "border-gray-300 dark:border-gray-600",
                                        ].join(" ")}
                                        onClick={() => this.setBugRange(days)}
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
                                    key: "occurrences",
                                    label: "Occurrences",
                                    color: "#dc2626",
                                },
                            ]}
                        />
                        <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
                            <div className="mb-3">
                                <h2 className="text-lg font-semibold">
                                    Grouped Bugs
                                </h2>
                            </div>
                            <div className="mt-4 overflow-x-auto">
                                <table className="w-full min-w-[760px] text-left text-sm">
                                    <thead>
                                        <tr className="border-b dark:border-gray-700">
                                            <th className="p-2">Bug</th>
                                            <th className="p-2">Status</th>
                                            <th className="p-2">Severity</th>
                                            <th className="p-2">Occurrences</th>
                                            <th className="p-2">Last Seen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {bugs.map((bug) => (
                                            <tr
                                                key={bug.id}
                                                className="cursor-pointer border-t hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                                                onClick={() => {
                                                    this.selectBug(bug);
                                                }}
                                            >
                                                <td className="p-2">
                                                    {bug.title}
                                                </td>
                                                <td className="p-2">
                                                    {bug.status}
                                                </td>
                                                <td className="p-2">
                                                    {bug.severity ? (
                                                        <SeverityBadge
                                                            severity={
                                                                bug.severity
                                                            }
                                                        />
                                                    ) : (
                                                        "-"
                                                    )}
                                                </td>
                                                <td className="p-2">
                                                    {bug.occurrence_count}
                                                </td>
                                                <td className="p-2">
                                                    {bug.last_seen_at ?? "-"}
                                                </td>
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
                {selectedEntry && (
                    <LogSidePeek
                        entry={selectedEntry}
                        onClose={() => this.closeEntry()}
                    />
                )}
                {selectedBug && (
                    <BugSidePeek
                        bug={selectedBug}
                        onClose={() => this.closeBug()}
                    />
                )}
            </div>
        );
    }
}
