import React, { useCallback, useEffect, useState } from "react";
import {
    fetchLogEntries,
    fetchLogFiles,
    fetchLogSummary,
} from "../ajax/logs-api";
import {
    LogEntriesPage,
    LogEntry,
    LogFileInfo,
    LogFilters,
    LogSummary,
    SEVERITIES,
} from "../types/logs-dashboard";

const SEVERITY_COLORS: Record<string, string> = {
    emergency: "bg-red-700 text-white",
    alert: "bg-red-600 text-white",
    critical: "bg-red-500 text-white",
    error: "bg-red-400 text-white",
    warning: "bg-yellow-400 text-gray-900",
    notice: "bg-blue-400 text-white",
    info: "bg-blue-300 text-gray-900",
    debug: "bg-gray-300 text-gray-800",
    unknown: "bg-gray-200 text-gray-700",
};

const BAR_H = 100;
const BAR_W = 600;
const PAD_L = 48;
const PAD_R = 16;
const PAD_T = 8;
const PAD_B = 28;
const INNER_W = BAR_W - PAD_L - PAD_R;
const INNER_H = BAR_H - PAD_T - PAD_B;

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

function SummaryChart({
    points,
}: {
    points: Array<{ period: string; count: number }>;
}) {
    if (points.length === 0)
        return <p className="text-sm text-gray-500">No chart data.</p>;

    const maxVal = Math.max(...points.map((p) => p.count), 1);
    const n = points.length;
    const barW = n > 0 ? Math.max(2, INNER_W / n - 1) : 0;

    return (
        <svg
            viewBox={`0 0 ${BAR_W} ${BAR_H}`}
            className="w-full"
            style={{ minHeight: 80 }}
        >
            {points.map((p, i) => {
                const h = (p.count / maxVal) * INNER_H;
                const x = PAD_L + (INNER_W * i) / Math.max(n, 1) - barW / 2;
                const y = PAD_T + INNER_H - h;

                return (
                    <rect
                        key={p.period}
                        x={x}
                        y={y}
                        width={barW}
                        height={h}
                        fill="#6366f1"
                        opacity={0.8}
                    >
                        <title>
                            {p.period}: {p.count}
                        </title>
                    </rect>
                );
            })}
            <line
                x1={PAD_L}
                x2={PAD_L + INNER_W}
                y1={PAD_T + INNER_H}
                y2={PAD_T + INNER_H}
                stroke="currentColor"
                strokeOpacity={0.25}
                strokeWidth={1}
            />
        </svg>
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

function LogEntryRow({ entry }: { entry: LogEntry }) {
    const [expanded, setExpanded] = useState(false);

    return (
        <tr className="border-t align-top dark:border-gray-700">
            <td className="p-2 text-xs text-gray-500 whitespace-nowrap">
                {entry.timestamp ?? "—"}
            </td>
            <td className="p-2">
                <SeverityBadge severity={entry.severity} />
            </td>
            <td className="p-2 text-xs">{entry.channel ?? "—"}</td>
            <td className="p-2 text-sm">
                <div>{entry.message}</div>
                {entry.context && (
                    <button
                        className="mt-1 text-xs text-blue-500 underline"
                        onClick={() => setExpanded(!expanded)}
                    >
                        {expanded ? "Hide context" : "Show context"}
                    </button>
                )}
                {expanded && entry.context && (
                    <pre className="mt-1 max-w-xl overflow-x-auto rounded bg-gray-100 p-2 text-xs dark:bg-gray-800">
                        {entry.context}
                    </pre>
                )}
            </td>
        </tr>
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
                const [entriesData, summaryData] = await Promise.all([
                    fetchLogEntries(fileKey, f, p),
                    fetchLogSummary(fileKey, f),
                ]);

                setEntries(entriesData);
                setSummary(summaryData);
            } catch {
                setError("Could not load log entries.");
            }
        },
        [],
    );

    useEffect(() => {
        if (selectedFile) void loadData(selectedFile, filters, page);
    }, [selectedFile, filters, page, loadData]);

    useEffect(() => {
        if (!selectedFile) return;
        const interval = window.setInterval(() => {
            void loadData(selectedFile, filters, page);
        }, 60000);
        return () => window.clearInterval(interval);
    }, [selectedFile, filters, page, loadData]);

    return (
        <div className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
            {loading && (
                <p role="status" aria-live="polite">
                    Loading log files…
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
                    <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
                        <h2 className="mb-3 text-lg font-semibold">Summary</h2>
                        <div className="mb-4 flex flex-wrap gap-2">
                            {Object.entries(summary.by_severity).map(
                                ([sev, cnt]) => (
                                    <span
                                        key={sev}
                                        className={`rounded px-2 py-1 text-xs font-semibold ${SEVERITY_COLORS[sev] ?? SEVERITY_COLORS.unknown}`}
                                    >
                                        {sev}: {cnt}
                                    </span>
                                ),
                            )}
                            {Object.keys(summary.by_severity).length === 0 && (
                                <span className="text-sm text-gray-500">
                                    No entries.
                                </span>
                            )}
                        </div>
                        <SummaryChart points={summary.chart} />
                    </section>

                    <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
                        <h2 className="mb-3 text-lg font-semibold">
                            Log Entries
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
                            <table className="w-full min-w-[700px] text-left text-sm">
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
                                    </tr>
                                </thead>
                                <tbody>
                                    {entries.data.map((entry, idx) => (
                                        <LogEntryRow
                                            key={`${entry.timestamp ?? "raw"}-${idx}`}
                                            entry={entry}
                                        />
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
        </div>
    );
}
