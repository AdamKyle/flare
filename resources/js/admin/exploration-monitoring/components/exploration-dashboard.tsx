import React from "react";
import "../types/echo-window";
import {
    fetchExplorationActive,
    fetchExplorationChart,
    fetchExplorationLogs,
    fetchExplorationSummary,
} from "../ajax/exploration-api";
import {
    ActiveExplorer,
    ExplorationChartPoint,
    ExplorationFilters,
    ExplorationLogRow,
    ExplorationSummary,
    Paginated,
} from "../types/exploration-monitoring";
import ExplorationDashboardState from "../types/exploration-dashboard-state";
import { DAY_OPTIONS } from "../values/filter-options";
import ActiveExplorersTable from "./active-explorers-table";
import ExplorationLogsTable from "./exploration-logs-table";
import MonitoringCard from "./monitoring-card";
import MonitoringStatusChart from "../../monitoring/components/monitoring-status-chart";

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
    stopped_by_player: false,
    date_from: "",
    date_to: "",
    days: "7",
};

export default class ExplorationDashboard extends React.Component<
    Record<string, never>,
    ExplorationDashboardState
> {
    private debounceTimer?: ReturnType<typeof setTimeout>;

    public constructor(props: Record<string, never>) {
        super(props);

        this.state = {
            loading: true,
            error: "",
            activeExplorers: [],
            logs: emptyPage(),
            summary: emptySummary,
            chart: [],
            filters: defaultFilters,
            logPage: 1,
            days: "7",
        };
    }

    componentDidMount() {
        void this.refresh();
        this.listenForUpdates();
    }

    componentWillUnmount() {
        clearTimeout(this.debounceTimer);
        window.Echo?.leave("admin-monitoring-exploration");
    }

    listenForUpdates() {
        const channelName = "admin-monitoring-exploration";
        const channel = window.Echo?.private(channelName);

        channel?.listen(".exploration.monitoring.updated", () => {
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
            const [active, logsData, summaryData, chartData] =
                await Promise.all([
                    fetchExplorationActive(),
                    fetchExplorationLogs(
                        this.state.filters,
                        this.state.logPage,
                    ),
                    fetchExplorationSummary(this.state.days),
                    fetchExplorationChart(this.state.days),
                ]);

            this.setState({
                activeExplorers: active,
                logs: logsData,
                summary: summaryData,
                chart: chartData,
            });
        } catch {
            this.setState({
                error: "Exploration monitoring data could not be loaded.",
            });
        } finally {
            this.setState({
                loading: false,
            });
        }
    }

    applyTableFilter(nextFilters: Partial<ExplorationFilters>) {
        this.setState(
            {
                filters: { ...defaultFilters, ...nextFilters },
                logPage: 1,
            },
            () => {
                void this.refresh();
                window.setTimeout(() => {
                    document
                        .getElementById("exploration-logs-table")
                        ?.scrollIntoView({
                            behavior: "smooth",
                            block: "start",
                        });
                }, 0);
            },
        );
    }

    updateFilters(nextFilters: ExplorationFilters) {
        this.setState(
            {
                filters: nextFilters,
                logPage: 1,
            },
            () => void this.refresh(),
        );
    }

    updateLogPage(page: number) {
        this.setState(
            {
                logPage: page,
            },
            () => void this.refresh(),
        );
    }

    updateDays(days: string) {
        this.setState(
            {
                days,
            },
            () => void this.refresh(),
        );
    }

    summaryCards() {
        return [
            { label: "Total Runs", value: this.state.summary.total_runs },
            {
                label: "Stopped by Player",
                value: this.state.summary.stopped_by_player,
            },
            {
                label: "Total Kills",
                value: this.state.summary.total_kills.toLocaleString(),
            },
            {
                label: "Total XP",
                value: this.state.summary.total_xp_gained.toLocaleString(),
            },
            {
                label: "Skill XP",
                value: this.state.summary.total_skill_xp_gained.toLocaleString(),
            },
        ];
    }

    renderSummaryCards() {
        return this.summaryCards().map(({ label, value }) => (
            <button
                key={label}
                type="button"
                className="text-left"
                onClick={() => {
                    if (label === "Stopped by Player") {
                        this.applyTableFilter({ stopped_by_player: true });
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
        ));
    }

    render() {
        return (
            <div className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
                {this.state.loading && (
                    <p role="status" aria-live="polite">
                        Loading exploration data…
                    </p>
                )}
                {this.state.error && (
                    <p
                        className="rounded border border-red-400 bg-red-50 p-3 text-red-800 dark:bg-red-950 dark:text-red-100"
                        role="alert"
                    >
                        {this.state.error}
                    </p>
                )}

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    {this.renderSummaryCards()}
                </div>

                <div>
                    <label className="mb-3 block text-sm font-medium">
                        Period
                        <select
                            className="ml-2 rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                            value={this.state.days}
                            onChange={(event) =>
                                this.updateDays(event.target.value)
                            }
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
                        points={this.state.chart}
                        series={[
                            { key: "runs", label: "Runs", color: "#22c55e" },
                            { key: "kills", label: "Kills", color: "#16a34a" },
                            { key: "xp", label: "XP", color: "#3b82f6" },
                            {
                                key: "skill_xp",
                                label: "Skill XP",
                                color: "#a855f7",
                            },
                            {
                                key: "active",
                                label: "Active",
                                color: "#f59e0b",
                                dash: "2,2",
                            },
                            {
                                key: "completed",
                                label: "Completed",
                                color: "#14b8a6",
                            },
                        ]}
                    />
                </div>

                <ActiveExplorersTable explorers={this.state.activeExplorers} />

                <ExplorationLogsTable
                    logs={this.state.logs}
                    filters={this.state.filters}
                    onFiltersChange={(nextFilters: ExplorationFilters) =>
                        this.updateFilters(nextFilters)
                    }
                    onPageChange={(page: number) => this.updateLogPage(page)}
                />
            </div>
        );
    }
}
