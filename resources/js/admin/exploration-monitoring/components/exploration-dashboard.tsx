import React, { useCallback, useEffect, useState } from "react";
import {
    fetchExplorationActive,
    fetchExplorationChart,
    fetchExplorationLogs,
    fetchExplorationSummary,
} from "../ajax/exploration-api";
import useExplorationLiveRefresh from "../hooks/use-exploration-live-refresh";
import {
    ActiveExplorer,
    ExplorationChartPoint,
    ExplorationFilters,
    ExplorationLogRow,
    ExplorationSummary,
    Paginated,
} from "../types/exploration-monitoring";
import { DAY_OPTIONS } from "../values/filter-options";
import ActiveExplorersTable from "./active-explorers-table";
import ExplorationLogsTable from "./exploration-logs-table";
import MonitoringCard from "./monitoring-card";
import SimpleBarChart from "./simple-bar-chart";

const emptyPage = <T,>(): Paginated<T> => ({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
});

const emptySummary: ExplorationSummary = {
    total_runs: 0,
    stopped_by_player: 0,
    total_kills: 0,
    total_xp_gained: 0,
    total_skill_xp_gained: 0,
};

const defaultFilters: ExplorationFilters = {
    character_name: "",
    stopped_reason: "",
    date_from: "",
    date_to: "",
    days: "7",
};

export default function ExplorationDashboard() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [activeExplorers, setActiveExplorers] = useState<ActiveExplorer[]>(
        [],
    );
    const [logs, setLogs] = useState<Paginated<ExplorationLogRow>>(emptyPage());
    const [summary, setSummary] = useState<ExplorationSummary>(emptySummary);
    const [chart, setChart] = useState<ExplorationChartPoint[]>([]);
    const [filters, setFilters] = useState<ExplorationFilters>(defaultFilters);
    const [logPage, setLogPage] = useState(1);
    const [days, setDays] = useState("7");

    const refresh = useCallback(async () => {
        setError("");

        try {
            const [active, logsData, summaryData, chartData] =
                await Promise.all([
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
            setError("Exploration monitoring data could not be loaded.");
        } finally {
            setLoading(false);
        }
    }, [filters, logPage, days]);

    useEffect(() => {
        void refresh();
    }, [refresh]);

    useExplorationLiveRefresh(refresh);

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
                    { label: "Total Runs", value: summary.total_runs },
                    {
                        label: "Stopped by Player",
                        value: summary.stopped_by_player,
                    },
                    {
                        label: "Total Kills",
                        value: summary.total_kills.toLocaleString(),
                    },
                    {
                        label: "Total XP",
                        value: summary.total_xp_gained.toLocaleString(),
                    },
                    {
                        label: "Skill XP",
                        value: summary.total_skill_xp_gained.toLocaleString(),
                    },
                ].map(({ label, value }) => (
                    <MonitoringCard key={label}>
                        <div className="text-sm text-gray-600 dark:text-gray-300">
                            {label}
                        </div>
                        <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                            {value}
                        </div>
                    </MonitoringCard>
                ))}
            </div>

            <MonitoringCard title="Exploration Runs per Period">
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
                <SimpleBarChart
                    title="Runs per day"
                    points={chart.map((p) => ({
                        period: p.period,
                        value: p.runs,
                    }))}
                    color="#22c55e"
                />
            </MonitoringCard>

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
