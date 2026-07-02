import React from "react";
import "../types/echo-window";
import {
    fetchDelveActive,
    fetchDelveChart,
    fetchDelveRuns,
    fetchDelveSummary,
} from "../ajax/delve-api";
import {
    ActiveDelveRunner,
    DelveChartPoint,
    DelveFilters,
    DelveRunRow,
    DelveSummary,
    Paginated,
} from "../types/delve-monitoring";
import DelveDashboardState from "../types/delve-dashboard-state";
import { DAY_OPTIONS } from "../values/filter-options";
import MonitorCard from "./monitor-card";
import MonitoringStatusChart from "../../monitoring/components/monitoring-status-chart";
import PaginationControls from "./pagination-controls";
import RunLogDetails from "./run-log-details";

const emptyPage = <T,>(): Paginated<T> => ({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
});

const defaultFilters: DelveFilters = {
    character_name: "",
    date_from: "",
    date_to: "",
    status: "",
    outcome: "",
};

export default class DelveDashboard extends React.Component<
    Record<string, never>,
    DelveDashboardState
> {
    private debounceTimer?: ReturnType<typeof setTimeout>;

    public constructor(props: Record<string, never>) {
        super(props);

        this.state = {
            loading: true,
            error: "",
            active: [],
            runs: emptyPage(),
            summary: {
                total_runs: 0,
                active: 0,
                completed: 0,
                total_survived: 0,
                total_died: 0,
                total_timeout: 0,
            },
            chart: [],
            filters: defaultFilters,
            page: 1,
            days: "7",
        };
    }

    componentDidMount() {
        void this.refresh();
        this.listenForUpdates();
    }

    componentWillUnmount() {
        clearTimeout(this.debounceTimer);
        window.Echo?.leave("admin-monitoring-delve");
    }

    listenForUpdates() {
        const channelName = "admin-monitoring-delve";
        const channel = window.Echo?.private(channelName);

        channel?.listen(".delve.monitoring.updated", () => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                void this.refresh();
            }, 500);
        });
    }

    async refresh() {
        this.setState({
            error: "",
        });

        try {
            const [activeData, runsData, summaryData, chartData] =
                await Promise.all([
                    fetchDelveActive(),
                    fetchDelveRuns(this.state.filters, this.state.page),
                    fetchDelveSummary(this.state.days),
                    fetchDelveChart(this.state.days),
                ]);

            this.setState({
                active: activeData,
                runs: runsData,
                summary: summaryData,
                chart: chartData,
            });
        } catch {
            this.setState({
                error: "Delve monitoring data could not be loaded.",
            });
        } finally {
            this.setState({
                loading: false,
            });
        }
    }

    applyTableFilter(nextFilters: Partial<DelveFilters>) {
        this.setState(
            {
                filters: { ...defaultFilters, ...nextFilters },
                page: 1,
            },
            () => {
                void this.refresh();
                window.setTimeout(() => {
                    document
                        .getElementById("delve-runs-table")
                        ?.scrollIntoView({
                            behavior: "smooth",
                            block: "start",
                        });
                }, 0);
            },
        );
    }

    setFilters(filters: DelveFilters) {
        this.setState(
            {
                filters,
                page: 1,
            },
            () => void this.refresh(),
        );
    }

    setPage(page: number) {
        this.setState(
            {
                page,
            },
            () => void this.refresh(),
        );
    }

    setDays(days: string) {
        this.setState(
            {
                days,
            },
            () => void this.refresh(),
        );
    }

    render() {
        const { active, chart, days, error, filters, loading, runs, summary } =
            this.state;

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
                                    this.applyTableFilter({ status: "active" });
                                } else if (label === "Completed") {
                                    this.applyTableFilter({
                                        status: "completed",
                                    });
                                } else if (label === "Survived") {
                                    this.applyTableFilter({
                                        outcome: "survived",
                                    });
                                } else if (label === "Died") {
                                    this.applyTableFilter({ outcome: "died" });
                                } else if (label === "Timeout") {
                                    this.applyTableFilter({
                                        outcome: "timeout",
                                    });
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
                            onChange={(e) => this.setDays(e.target.value)}
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
                            {
                                key: "active",
                                label: "Active",
                                color: "#3b82f6",
                            },
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
                                                {runner.increase_percentage !==
                                                null
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
                                                            runner
                                                                .outcome_counts
                                                                .survived
                                                        }{" "}
                                                        ✗
                                                        {
                                                            runner
                                                                .outcome_counts
                                                                .died
                                                        }{" "}
                                                        ⏱
                                                        {
                                                            runner
                                                                .outcome_counts
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
                                    className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                                    type="text"
                                    value={filters.character_name}
                                    onChange={(e) => {
                                        this.setFilters({
                                            ...filters,
                                            character_name: e.target.value,
                                        });
                                    }}
                                />
                            </label>
                            <label className="text-sm font-medium">
                                Date from
                                <input
                                    className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
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
                                    className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
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
                                                {run.increase_enemy_strength ??
                                                    "—"}
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
                            onPageChange={(page: number) => this.setPage(page)}
                        />
                    </MonitorCard>
                </div>
            </div>
        );
    }
}
