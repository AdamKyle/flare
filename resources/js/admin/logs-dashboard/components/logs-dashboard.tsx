import React, { useCallback, useEffect, useMemo, useState } from "react";
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

const SEVERITY_COLORS: Record<string, string> = {
    emergency: "bg-red-700 text-white",
    alert: "bg-red-600 text-white",
    critical: "bg-red-500 text-white",
    error: "bg-red-400 text-white",
    fatal: "bg-red-700 text-white",
    warning: "bg-yellow-400 text-gray-900",
    notice: "bg-blue-400 text-white",
    info: "bg-blue-300 text-gray-900",
    debug: "bg-gray-300 text-gray-800",
    unknown: "bg-gray-200 text-gray-700",
};

function SeverityBadge({ severity }: { severity: string }) {
    const cls =
        SEVERITY_COLORS[severity.toLowerCase()] ?? SEVERITY_COLORS.unknown;
    return (
        <span
            className={`inline-block rounded px-1.5 py-0.5 text-xs font-semibold uppercase ${cls}`}
        >
            {severity}
        </span>
    );
}

function PaginationControls({
    currentPage,
    lastPage,
    onPageChange,
}: {
    currentPage: number;
    lastPage: number;
    onPageChange: (p: number) => void;
}) {
    return (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
            <span className="text-sm text-gray-600 dark:text-gray-300">
                Page {currentPage} of {lastPage}
            </span>
            <div className="flex gap-2">
                <button
                    className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
                    disabled={currentPage <= 1}
                    onClick={() => onPageChange(currentPage - 1)}
                >
                    Previous
                </button>
                <button
                    className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
                    disabled={currentPage >= lastPage}
                    onClick={() => onPageChange(currentPage + 1)}
                >
                    Next
                </button>
            </div>
        </div>
    );
}

function DetailBlock({
    label,
    value,
    pre = false,
}: {
    label: string;
    value: React.ReactNode;
    pre?: boolean;
}) {
    if (value === null || value === undefined || value === "") {
        return null;
    }

    return (
        <div>
            <dt className="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                {label}
            </dt>
            <dd
                className={
                    pre
                        ? "mt-1 max-h-72 overflow-y-auto overflow-x-hidden whitespace-pre-wrap break-all rounded bg-gray-100 p-2 text-xs dark:bg-gray-800"
                        : "mt-1 break-words text-sm text-gray-900 dark:text-gray-100"
                }
            >
                {value}
            </dd>
        </div>
    );
}

function LogSidePeek({
    entry,
    onClose,
}: {
    entry: LogEntry;
    onClose: () => void;
}) {
    const rawFormatted = (() => {
        if (!entry.raw_log_entry) return null;
        try {
            return JSON.stringify(JSON.parse(entry.raw_log_entry), null, 2);
        } catch {
            return entry.raw_log_entry;
        }
    })();

    return (
        <aside className="fixed inset-y-0 right-0 z-40 w-full max-w-xl overflow-y-auto overflow-x-hidden border-l border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
            <div className="mb-4 flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="text-lg font-semibold">Log Detail</h3>
                    <p className="break-all text-sm text-gray-500">
                        {entry.file_path ?? entry.channel ?? "Log entry"}
                    </p>
                </div>
                <button
                    className="shrink-0 rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600"
                    onClick={onClose}
                >
                    Close
                </button>
            </div>
            <dl className="space-y-4">
                <DetailBlock label="Timestamp" value={entry.timestamp} />
                <DetailBlock
                    label="Level"
                    value={<SeverityBadge severity={entry.severity} />}
                />
                <DetailBlock
                    label="Channel/File"
                    value={entry.file_path ?? entry.channel}
                />
                <DetailBlock label="Message" value={entry.message} />
                <DetailBlock
                    label="Exception Class"
                    value={entry.exception_class}
                />
                <DetailBlock
                    label="File/Line"
                    value={
                        entry.exception_file
                            ? `${entry.exception_file}${entry.exception_line ? `:${entry.exception_line}` : ""}`
                            : null
                    }
                />
                <DetailBlock label="Context" value={entry.context} pre />
                <DetailBlock
                    label="Stack Trace"
                    value={entry.stack_trace}
                    pre
                />
                <DetailBlock label="Raw Log Entry" value={rawFormatted} pre />
            </dl>
        </aside>
    );
}

function BugSidePeek({
    bug,
    onClose,
}: {
    bug: SystemBugReport;
    onClose: () => void;
}) {
    return (
        <aside className="fixed inset-y-0 right-0 z-40 w-full max-w-xl overflow-y-auto border-l border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
            <div className="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 className="text-lg font-semibold">{bug.title}</h3>
                    <p className="text-sm text-gray-500">
                        {bug.occurrence_count} occurrence
                        {bug.occurrence_count === 1 ? "" : "s"}
                    </p>
                </div>
                <button
                    className="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600"
                    onClick={onClose}
                >
                    Close
                </button>
            </div>
            <dl className="space-y-4">
                <DetailBlock label="Fingerprint" value={bug.fingerprint} />
                <DetailBlock label="Status" value={bug.status} />
                <DetailBlock label="Severity" value={bug.severity} />
                <DetailBlock label="First Seen" value={bug.first_seen_at} />
                <DetailBlock label="Last Seen" value={bug.last_seen_at} />
                <DetailBlock
                    label="Latest Message"
                    value={bug.latest_message}
                />
                <DetailBlock
                    label="Latest Stack Trace"
                    value={bug.latest_stack_trace}
                    pre
                />
                <DetailBlock
                    label="Latest Raw Log Entry"
                    value={bug.latest_raw_log_entry}
                    pre
                />
            </dl>
            <h4 className="mt-5 text-sm font-semibold">Occurrence History</h4>
            <div className="mt-2 space-y-2">
                {bug.occurrences.map((occurrence, index) => (
                    <div
                        key={`${occurrence.occurred_at ?? "unknown"}-${index}`}
                        className="rounded border border-gray-200 p-3 text-sm dark:border-gray-700"
                    >
                        <div className="flex flex-wrap items-center gap-2">
                            <span>
                                {occurrence.occurred_at ?? "Unknown time"}
                            </span>
                            {occurrence.level && (
                                <SeverityBadge severity={occurrence.level} />
                            )}
                        </div>
                        <p className="mt-1 text-gray-700 dark:text-gray-300">
                            {occurrence.message}
                        </p>
                    </div>
                ))}
                {bug.occurrences.length === 0 && (
                    <p className="text-sm text-gray-500">
                        No occurrences loaded.
                    </p>
                )}
            </div>
        </aside>
    );
}

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

export default function LogsDashboard() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [files, setFiles] = useState<LogFileInfo[]>([]);
    const [selectedFile, setSelectedFile] = useState<string>("");
    const [filters, setFilters] = useState<LogFilters>(defaultFilters);
    const [page, setPage] = useState(1);
    const [entries, setEntries] = useState<LogEntriesPage>(emptyPage());
    const [summary, setSummary] = useState<LogSummary>({
        total: 0,
        by_severity: {},
        chart: [],
    });
    const [newEntries, setNewEntries] = useState<LogEntry[]>([]);
    const [bugs, setBugs] = useState<SystemBugReport[]>([]);
    const [bugChart, setBugChart] = useState<
        Array<{ period: string; occurrences: number }>
    >([]);
    const [bugRange, setBugRange] = useState(30);
    const [selectedEntry, setSelectedEntry] = useState<LogEntry | null>(null);
    const [selectedBug, setSelectedBug] = useState<SystemBugReport | null>(
        null,
    );

    const tableRef = React.useRef<HTMLElement | null>(null);

    useEffect(() => {
        fetchLogFiles()
            .then((f) => {
                setFiles(f);
                const first = f.find((fi) => fi.exists);
                if (first && !selectedFile) setSelectedFile(first.key);
            })
            .catch(() => setError("Could not load log file list."))
            .finally(() => setLoading(false));
    }, []);

    const loadData = useCallback(
        async (fileKey: string, f: LogFilters, p: number) => {
            if (!fileKey) return;

            setError("");

            try {
                const [entriesData, summaryData, bugData, bugChartData] =
                    await Promise.all([
                        fetchLogEntries(fileKey, f, p),
                        fetchLogSummary(fileKey, f),
                        fetchSystemBugs(),
                        fetchBugChart(bugRange),
                    ]);

                setEntries(entriesData);
                setSummary(summaryData);
                setBugs(bugData);
                setBugChart(bugChartData);
            } catch {
                setError("Could not load log entries.");
            }
        },
        [bugRange],
    );

    useEffect(() => {
        if (selectedFile) void loadData(selectedFile, filters, page);
    }, [selectedFile, filters, page, loadData]);

    useEffect(() => {
        if (!selectedFile) return;
        void pollLogs(selectedFile, filters)
            .then((payload) => {
                setNewEntries(payload.entries);
                setSummary(payload.summary);
                setFiles(payload.files);
                setBugs(payload.bugs);
                setBugChart(payload.bug_chart);
            })
            .catch(() => {});
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedFile]);

    useEffect(() => {
        if (!selectedFile) return;
        const interval = window.setInterval(() => {
            pollLogs(selectedFile, filters)
                .then((payload) => {
                    setNewEntries(payload.entries);
                    setSummary(payload.summary);
                    setFiles(payload.files);
                    setBugs(payload.bugs);
                    setBugChart(payload.bug_chart);
                    void loadData(selectedFile, filters, page);
                })
                .catch(() => setError("Could not poll log entries."));
        }, 60000);
        return () => window.clearInterval(interval);
    }, [selectedFile, filters, page, loadData]);

    const chartPoints = useMemo(
        () =>
            summary.chart.map((point) => ({
                period: point.period,
                entries: point.count,
            })),
        [summary.chart],
    );

    const setSeverityFilter = (severity: string) => {
        setFilters({ ...filters, severity });
        setPage(1);
        window.setTimeout(() => {
            tableRef.current?.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
        }, 0);
    };

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
                <div className="flex flex-wrap gap-2">
                    {files.map((f) => (
                        <button
                            key={f.key}
                            disabled={!f.exists}
                            onClick={() => {
                                setSelectedFile(f.key);
                                setPage(1);
                            }}
                            className={[
                                "rounded border px-3 py-1.5 text-sm transition-colors",
                                selectedFile === f.key
                                    ? "border-indigo-500 bg-indigo-50 font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200"
                                    : "border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-800",
                                !f.exists
                                    ? "cursor-not-allowed opacity-40"
                                    : "",
                            ].join(" ")}
                        >
                            {f.label}
                            {f.exists && (
                                <span className="ml-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    {(f.size_bytes / 1024).toFixed(1)}KB
                                </span>
                            )}
                            {!f.exists && (
                                <span className="ml-1.5 text-xs">
                                    (missing)
                                </span>
                            )}
                        </button>
                    ))}
                </div>
            </section>

            {selectedFile && (
                <>
                    <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <button
                            className="rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm dark:border-gray-700 dark:bg-gray-900"
                            onClick={() => setSeverityFilter("")}
                        >
                            <span className="text-sm text-gray-500">Total</span>
                            <span className="mt-1 block text-2xl font-semibold">
                                {summary.total}
                            </span>
                        </button>
                        {Object.entries(summary.by_severity).map(
                            ([sev, cnt]) => (
                                <button
                                    key={sev}
                                    className="rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm dark:border-gray-700 dark:bg-gray-900"
                                    onClick={() => setSeverityFilter(sev)}
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

                    <MonitoringStatusChart
                        title="Log Volume"
                        description="Parsed log entries by day for the selected channel and filters."
                        points={chartPoints}
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
                                                setSelectedBug(bug);
                                                setSelectedEntry(null);
                                            }}
                                        >
                                            <td className="p-2">{bug.title}</td>
                                            <td className="p-2">
                                                {bug.status}
                                            </td>
                                            <td className="p-2">
                                                {bug.severity ? (
                                                    <SeverityBadge
                                                        severity={bug.severity}
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

                    {newEntries.length > 0 && (
                        <section className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
                            {newEntries.length} new log{" "}
                            {newEntries.length === 1 ? "entry" : "entries"} read
                            during the latest poll.
                        </section>
                    )}

                    <section
                        ref={tableRef}
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
                                const fromStr = from.toISOString().slice(0, 10);
                                const toStr = new Date()
                                    .toISOString()
                                    .slice(0, 10);
                                return (
                                    <button
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
                                className="rounded border border-gray-300 px-3 py-1 text-xs hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-800"
                                onClick={() => {
                                    setFilters({
                                        ...filters,
                                        date_from: "",
                                        date_to: "",
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
                                        <th className="p-2 whitespace-nowrap">
                                            Timestamp
                                        </th>
                                        <th className="p-2">Severity</th>
                                        <th className="p-2">Channel</th>
                                        <th className="p-2">Message</th>
                                        <th className="p-2">Exception</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {entries.data.map((entry, idx) => (
                                        <tr
                                            key={`${entry.timestamp ?? "raw"}-${idx}`}
                                            className="cursor-pointer border-t align-top hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                                            onClick={() => {
                                                setSelectedEntry(entry);
                                                setSelectedBug(null);
                                            }}
                                        >
                                            <td className="p-2 text-xs text-gray-500 whitespace-nowrap">
                                                {entry.timestamp ?? "-"}
                                            </td>
                                            <td className="p-2">
                                                <SeverityBadge
                                                    severity={entry.severity}
                                                />
                                            </td>
                                            <td className="p-2 text-xs">
                                                {entry.channel ?? "-"}
                                            </td>
                                            <td className="p-2 text-sm">
                                                {entry.message}
                                            </td>
                                            <td className="p-2 text-xs">
                                                {entry.exception_class ?? "-"}
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
                        <PaginationControls
                            currentPage={entries.current_page}
                            lastPage={entries.last_page}
                            onPageChange={setPage}
                        />
                    </section>
                </>
            )}
            {selectedEntry && (
                <LogSidePeek
                    entry={selectedEntry}
                    onClose={() => setSelectedEntry(null)}
                />
            )}
            {selectedBug && (
                <BugSidePeek
                    bug={selectedBug}
                    onClose={() => setSelectedBug(null)}
                />
            )}
        </div>
    );
}
