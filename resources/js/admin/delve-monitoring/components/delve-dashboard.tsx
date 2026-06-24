import React, { useCallback, useEffect, useState } from "react";
import {
    fetchDelveActive,
    fetchDelveChart,
    fetchDelveRuns,
    fetchDelveSummary,
} from "../ajax/delve-api";
import useDelveMonitoringLiveRefresh from "../hooks/use-delve-live-refresh";
import {
    ActiveDelveRunner,
    DelveChartPoint,
    DelveFilters,
    DelveLogEntry,
    DelveRunRow,
    DelveSummary,
    Paginated,
} from "../types/delve-monitoring";
import { DAY_OPTIONS } from "../values/filter-options";
import MonitoringStatusChart from "../../monitoring/components/monitoring-status-chart";

const emptyPage = <T,>(): Paginated<T> => ({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
});

function MonitorCard({ children }: { children: React.ReactNode }) {
    return (
        <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
            {children}
        </section>
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

function RunLogDetails({ logs }: { logs: DelveLogEntry[] }) {
    const [open, setOpen] = useState(false);
    const [page, setPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(logs.length / 10));
    const rows = logs.slice((page - 1) * 10, page * 10);

    if (logs.length === 0) {
        return <span className="text-xs text-gray-400">No logs</span>;
    }

    return (
        <span>
            <button
                className="text-xs text-blue-500 underline"
                onClick={() => setOpen(!open)}
                aria-expanded={open}
            >
                {open ? "Hide logs" : `Show ${logs.length} log(s)`}
            </button>
            {open && (
                <div className="mt-2 overflow-x-auto rounded border border-gray-200 dark:border-gray-600">
                    <table className="w-full text-xs text-left">
                        <thead>
                            <tr className="border-b dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                                <th className="p-1">Outcome</th>
                                <th className="p-1">Pack size</th>
                                <th className="p-1">Enemy strength</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((log) => (
                                <tr
                                    key={log.id}
                                    className="border-t dark:border-gray-700"
                                >
                                    <td className="p-1">{log.outcome}</td>
                                    <td className="p-1">{log.pack_size}</td>
                                    <td className="p-1">
                                        {log.increased_enemy_strength !== null
                                            ? `${Math.round((log.increased_enemy_strength ?? 0) * 100)}%`
                                            : "—"}
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
                                    className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                                    disabled={page <= 1}
                                    onClick={() =>
                                        setPage(
                                            (currentPage) => currentPage - 1,
                                        )
                                    }
                                >
                                    Previous
                                </button>
                                <button
                                    className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                                    disabled={page >= totalPages}
                                    onClick={() =>
                                        setPage(
                                            (currentPage) => currentPage + 1,
                                        )
                                    }
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            )}
        </span>
    );
}

const defaultFilters: DelveFilters = {
    character_name: "",
    date_from: "",
    date_to: "",
    status: "",
    outcome: "",
};

export default function DelveDashboard() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [active, setActive] = useState<ActiveDelveRunner[]>([]);
    const [runs, setRuns] = useState<Paginated<DelveRunRow>>(emptyPage());
    const [summary, setSummary] = useState<DelveSummary>({
        total_runs: 0,
        active: 0,
        completed: 0,
        total_survived: 0,
        total_died: 0,
        total_timeout: 0,
    });
    const [chart, setChart] = useState<DelveChartPoint[]>([]);
    const [filters, setFilters] = useState<DelveFilters>(defaultFilters);
    const [page, setPage] = useState(1);
    const [days, setDays] = useState("7");

    const refresh = useCallback(async () => {
        setError("");

        try {
            const [activeData, runsData, summaryData, chartData] =
                await Promise.all([
                    fetchDelveActive(),
                    fetchDelveRuns(filters, page),
                    fetchDelveSummary(days),
                    fetchDelveChart(days),
                ]);

            setActive(activeData);
            setRuns(runsData);
            setSummary(summaryData);
            setChart(chartData);
        } catch {
            setError("Delve monitoring data could not be loaded.");
        } finally {
            setLoading(false);
        }
    }, [filters, page, days]);

    useEffect(() => {
        void refresh();
    }, [refresh]);

    useDelveMonitoringLiveRefresh(refresh);

    const applyTableFilter = (nextFilters: Partial<DelveFilters>) => {
        setFilters({ ...defaultFilters, ...nextFilters });
        setPage(1);
        window.setTimeout(() => {
            document
                .getElementById("delve-runs-table")
                ?.scrollIntoView({ behavior: "smooth", block: "start" });
        }, 0);
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
                {[
                    { label: "Total Runs", value: summary.total_runs },
                    { label: "Active", value: summary.active },
                    { label: "Completed", value: summary.completed },
                    { label: "Survived", value: summary.total_survived },
                    { label: "Died", value: summary.total_died },
                    { label: "Timeout", value: summary.total_timeout },
                ].map(({ label, value }) => (
                    <button
                        key={label}
                        type="button"
                        className="text-left"
                        onClick={() => {
                            if (label === "Active") {
                                applyTableFilter({ status: "active" });
                            } else if (label === "Completed") {
                                applyTableFilter({ status: "completed" });
                            } else if (label === "Survived") {
                                applyTableFilter({ outcome: "survived" });
                            } else if (label === "Died") {
                                applyTableFilter({ outcome: "died" });
                            } else if (label === "Timeout") {
                                applyTableFilter({ outcome: "timeout" });
                            }
                        }}
                    >
                        <MonitorCard>
                            <div className="text-sm text-gray-600 dark:text-gray-300">
                                {label}
                            </div>
                            <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                                {value}
                            </div>
                        </MonitorCard>
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
                    title="Delve Runs per Period"
                    description="Run, status, and outcome totals from retained Delve data."
                    points={chart}
                    series={[
                        { key: "runs", label: "Runs", color: "#f97316" },
                        { key: "active", label: "Active", color: "#3b82f6" },
                        {
                            key: "completed",
                            label: "Completed",
                            color: "#22c55e",
                        },
                        {
                            key: "survived",
                            label: "Survived",
                            color: "#16a34a",
                        },
                        {
                            key: "died",
                            label: "Died",
                            color: "#ef4444",
                            dash: "6,3",
                        },
                        {
                            key: "timeout",
                            label: "Timeout",
                            color: "#f59e0b",
                            dash: "2,2",
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
                                            {runner.character_name ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.increase_enemy_strength ??
                                                "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.increase_percentage !== null
                                                ? `${runner.increase_percentage}%`
                                                : "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.started_at ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.total_encounters}
                                        </td>
                                        <td className="p-2">
                                            {runner.avg_pack_size !== null
                                                ? runner.avg_pack_size
                                                : "—"}
                                        </td>
                                        <td className="p-2 text-xs">
                                            {runner.outcome_counts ? (
                                                <span>
                                                    ✓
                                                    {
                                                        runner.outcome_counts
                                                            .survived
                                                    }{" "}
                                                    ✗
                                                    {runner.outcome_counts.died}{" "}
                                                    ⏱
                                                    {
                                                        runner.outcome_counts
                                                            .timeout
                                                    }
                                                </span>
                                            ) : (
                                                "—"
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </MonitorCard>

            <div id="delve-runs-table">
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
                                    setFilters({
                                        ...filters,
                                        character_name: e.target.value,
                                    });
                                    setPage(1);
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
                                    <tr
                                        className="border-t dark:border-gray-700"
                                        key={run.id}
                                    >
                                        <td className="p-2">
                                            {run.character?.name ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {run.increase_enemy_strength ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {run.started_at ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {run.completed_at ?? "Active"}
                                        </td>
                                        <td className="p-2">
                                            <RunLogDetails
                                                logs={run.delve_logs ?? []}
                                            />
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
                    <PaginationControls
                        currentPage={runs.current_page}
                        lastPage={runs.last_page}
                        onPageChange={setPage}
                    />
                </MonitorCard>
            </div>
        </div>
    );
}
