import React from "react";
import "../types/echo-window";
import {
    fetchFactionLoyaltyActive,
    fetchFactionLoyaltyChart,
    fetchFactionLoyaltyRuns,
    fetchFactionLoyaltySummary,
} from "../ajax/faction-loyalty-api";
import {
    ActiveFactionLoyaltyRunner,
    FactionLoyaltyChartPoint,
    FactionLoyaltyFilters,
    FactionLoyaltyRunRow,
    FactionLoyaltySummary,
    Paginated,
} from "../types/faction-loyalty-monitoring";
import FactionLoyaltyDashboardState from "../types/faction-loyalty-dashboard-state";
import { DAY_OPTIONS } from "../values/filter-options";
import LogDetails from "./log-details";
import MonitorCard from "./monitor-card";
import MonitoringStatusChart from "../../monitoring/components/monitoring-status-chart";
import PaginationControls from "./pagination-controls";

const emptyPage = <T,>(): Paginated<T> => ({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
});

const defaultFilters: FactionLoyaltyFilters = {
    character_name: "",
    date_from: "",
    date_to: "",
    status: "",
    days: "7",
};

export default class FactionLoyaltyDashboard extends React.Component<
    Record<string, never>,
    FactionLoyaltyDashboardState
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
        window.Echo?.leave("admin-monitoring-faction-loyalty");
    }

    listenForUpdates() {
        const channelName = "admin-monitoring-faction-loyalty";
        const channel = window.Echo?.private(channelName);

        channel?.listen(".faction.loyalty.monitoring.updated", () => {
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
                    fetchFactionLoyaltyActive(),
                    fetchFactionLoyaltyRuns(
                        this.state.filters,
                        this.state.page,
                    ),
                    fetchFactionLoyaltySummary(this.state.days),
                    fetchFactionLoyaltyChart(this.state.days),
                ]);

            this.setState({
                active: activeData,
                runs: runsData,
                summary: summaryData,
                chart: chartData,
            });
        } catch {
            this.setState({
                error: "Faction loyalty monitoring data could not be loaded.",
            });
        } finally {
            this.setState({
                loading: false,
            });
        }
    }

    applyTableFilter(nextFilters: Partial<FactionLoyaltyFilters>) {
        this.setState(
            {
                filters: { ...defaultFilters, ...nextFilters },
                page: 1,
            },
            () => {
                void this.refresh();
                window.setTimeout(() => {
                    document
                        .getElementById("faction-loyalty-runs-table")
                        ?.scrollIntoView({
                            behavior: "smooth",
                            block: "start",
                        });
                }, 0);
            },
        );
    }

    setFilters(filters: FactionLoyaltyFilters) {
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
                        title="Faction Loyalty Runs per Period"
                        description="Run, active, and completed totals from faction loyalty automation data."
                        points={chart}
                        series={[
                            { key: "runs", label: "Runs", color: "#a855f7" },
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
                        ]}
                    />
                </div>

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
                                                {runner.last_fight_outcome ??
                                                    "—"}
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

                <div id="faction-loyalty-runs-table">
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
                                                {run.factionLoyaltyNpc?.npc
                                                    ?.name ?? "—"}
                                            </td>
                                            <td className="p-2">
                                                {run.last_automation_action ??
                                                    "—"}
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
                            onPageChange={(page: number) => this.setPage(page)}
                        />
                    </MonitorCard>
                </div>
            </div>
        );
    }
}
