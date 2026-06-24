import React, { useCallback, useEffect, useState } from "react";
import {
    fetchFactionLoyaltyActive,
    fetchFactionLoyaltyChart,
    fetchFactionLoyaltyRuns,
    fetchFactionLoyaltySummary,
} from "../ajax/faction-loyalty-api";
import useFactionLoyaltyLiveRefresh from "../hooks/use-faction-loyalty-live-refresh";
import {
    ActiveFactionLoyaltyRunner,
    FactionLoyaltyChartPoint,
    FactionLoyaltyFilters,
    FactionLoyaltyLog,
    FactionLoyaltyRunRow,
    FactionLoyaltySummary,
    Paginated,
} from "../types/faction-loyalty-monitoring";
import { DAY_OPTIONS } from "../values/filter-options";

const emptyPage = <T,>(): Paginated<T> => ({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
});

const BAR_H = 160;
const BAR_W = 600;
const PAD_L = 48;
const PAD_R = 16;
const PAD_T = 10;
const PAD_B = 32;
const INNER_W = BAR_W - PAD_L - PAD_R;
const INNER_H = BAR_H - PAD_T - PAD_B;

function MonitorCard({ children }: { children: React.ReactNode }) {
    return (
        <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
            {children}
        </section>
    );
}

function RunsBarChart({ points }: { points: FactionLoyaltyChartPoint[] }) {
    const maxVal = Math.max(...points.map((p) => p.runs), 1);
    const n = points.length;
    const barW = n > 0 ? Math.max(2, INNER_W / n - 2) : 0;

    return points.length === 0 ? (
        <p className="text-sm text-gray-500">No data in this period.</p>
    ) : (
        <svg
            viewBox={`0 0 ${BAR_W} ${BAR_H}`}
            className="w-full"
            style={{ minHeight: 100 }}
        >
            {points.map((p, i) => {
                const barH = (p.runs / maxVal) * INNER_H;
                const barX = PAD_L + (INNER_W * i) / Math.max(n, 1) - barW / 2;
                const barY = PAD_T + INNER_H - barH;

                return (
                    <rect
                        key={p.period}
                        x={barX}
                        y={barY}
                        width={barW}
                        height={barH}
                        fill="#a855f7"
                        opacity={0.8}
                    >
                        <title>
                            {p.period}: {p.runs}
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

function LogDetails({ log }: { log: FactionLoyaltyLog | null | undefined }) {
    const [open, setOpen] = useState(false);

    const fightLogs = log?.fight_logs ?? [];
    const craftingLogs = log?.crafting_logs ?? [];
    const hasLogs = fightLogs.length > 0 || craftingLogs.length > 0;

    if (!hasLogs) {
        return <span className="text-xs text-gray-400">No logs</span>;
    }

    return (
        <span>
            <button
                className="text-xs text-blue-500 underline"
                onClick={() => setOpen(!open)}
                aria-expanded={open}
            >
                {open
                    ? "Hide logs"
                    : `Show logs (${fightLogs.length} fight, ${craftingLogs.length} craft)`}
            </button>
            {open && (
                <div className="mt-2 space-y-2">
                    {fightLogs.length > 0 && (
                        <details open>
                            <summary className="text-xs font-medium cursor-pointer">
                                Fight logs ({fightLogs.length})
                            </summary>
                            <pre className="mt-1 max-h-40 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-gray-800">
                                {JSON.stringify(fightLogs, null, 2)}
                            </pre>
                        </details>
                    )}
                    {craftingLogs.length > 0 && (
                        <details open>
                            <summary className="text-xs font-medium cursor-pointer">
                                Crafting logs ({craftingLogs.length})
                            </summary>
                            <pre className="mt-1 max-h-40 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-gray-800">
                                {JSON.stringify(craftingLogs, null, 2)}
                            </pre>
                        </details>
                    )}
                </div>
            )}
        </span>
    );
}

const defaultFilters: FactionLoyaltyFilters = {
    character_name: "",
    date_from: "",
    date_to: "",
    days: "7",
};

export default function FactionLoyaltyDashboard() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [active, setActive] = useState<ActiveFactionLoyaltyRunner[]>([]);
    const [runs, setRuns] =
        useState<Paginated<FactionLoyaltyRunRow>>(emptyPage());
    const [summary, setSummary] = useState<FactionLoyaltySummary>({
        total_runs: 0,
        active: 0,
        completed: 0,
    });
    const [chart, setChart] = useState<FactionLoyaltyChartPoint[]>([]);
    const [filters, setFilters] =
        useState<FactionLoyaltyFilters>(defaultFilters);
    const [page, setPage] = useState(1);
    const [days, setDays] = useState("7");

    const refresh = useCallback(async () => {
        setError("");

        try {
            const [activeData, runsData, summaryData, chartData] =
                await Promise.all([
                    fetchFactionLoyaltyActive(),
                    fetchFactionLoyaltyRuns(filters, page),
                    fetchFactionLoyaltySummary(days),
                    fetchFactionLoyaltyChart(days),
                ]);

            setActive(activeData);
            setRuns(runsData);
            setSummary(summaryData);
            setChart(chartData);
        } catch {
            setError("Faction loyalty monitoring data could not be loaded.");
        } finally {
            setLoading(false);
        }
    }, [filters, page, days]);

    useEffect(() => {
        void refresh();
    }, [refresh]);

    useFactionLoyaltyLiveRefresh(refresh);

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
                {[
                    { label: "Total Runs", value: summary.total_runs },
                    { label: "Active", value: summary.active },
                    { label: "Completed", value: summary.completed },
                ].map(({ label, value }) => (
                    <MonitorCard key={label}>
                        <div className="text-sm text-gray-600 dark:text-gray-300">
                            {label}
                        </div>
                        <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                            {value}
                        </div>
                    </MonitorCard>
                ))}
            </div>

            <MonitorCard>
                <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
                    Runs per Period
                </h2>
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
                <RunsBarChart points={chart} />
            </MonitorCard>

            <MonitorCard>
                <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">
                    Currently Active
                </h2>
                {active.length === 0 ? (
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        No characters are currently in faction loyalty
                        automation.
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
                                            {runner.character_name ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.npc_name ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.last_action ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.started_at ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.last_fight_outcome ?? "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.last_fight_was_bounty_target ===
                                            null
                                                ? "—"
                                                : runner.last_fight_was_bounty_target
                                                  ? "Yes"
                                                  : "No"}
                                        </td>
                                        <td className="p-2">
                                            {runner.failed_bounty_monster_name ??
                                                "—"}
                                        </td>
                                        <td className="p-2">
                                            {runner.failed_crafting_item_name ??
                                                "—"}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </MonitorCard>

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
                                <tr
                                    className="border-t dark:border-gray-700"
                                    key={run.id}
                                >
                                    <td className="p-2">
                                        {run.character?.name ?? "—"}
                                    </td>
                                    <td className="p-2">
                                        {run.factionLoyaltyNpc?.npc?.name ??
                                            "—"}
                                    </td>
                                    <td className="p-2">
                                        {run.last_automation_action ?? "—"}
                                    </td>
                                    <td className="p-2">
                                        {run.started_at ?? "—"}
                                    </td>
                                    <td className="p-2">
                                        {run.completed_at ?? "Active"}
                                    </td>
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
                <PaginationControls
                    currentPage={runs.current_page}
                    lastPage={runs.last_page}
                    onPageChange={setPage}
                />
            </MonitorCard>
        </div>
    );
}
