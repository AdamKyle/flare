import React from "react";
import axios, { AxiosError } from "axios";
import ChartCard from "./components/chart-card";
import OnlineCharacterList from "./components/online-character-list";
import RankedTable from "./components/ranked-table";
import StatCard from "./components/stat-card";
import { AdminStatisticsDashboardSnapshot } from "./types/admin-statistics-dashboard";
import { handleUnauthenticatedResponse } from "../../game/lib/ajax/unauthenticated-response-handler";

interface DashboardState {
    snapshot: AdminStatisticsDashboardSnapshot | null;
    loading: boolean;
    refreshing: boolean;
    error: string;
    websocketStatus: "connected" | "unavailable";
}

export default class Dashboard extends React.Component<any, DashboardState> {
    private channelName = "admin-statistics-dashboard";
    private numberFormatter = new Intl.NumberFormat();

    constructor(props: any) {
        super(props);

        this.state = {
            snapshot: null,
            loading: true,
            refreshing: false,
            error: "",
            websocketStatus: "unavailable",
        };
    }

    componentDidMount() {
        void this.fetchSnapshot();
        this.subscribeToDashboardUpdates();
    }

    componentWillUnmount() {
        const echo = (window as any).Echo;

        if (echo) {
            echo.leave(this.channelName);
        }
    }

    async fetchSnapshot() {
        this.setState({ error: "", refreshing: true });

        try {
            const response = await axios.get<AdminStatisticsDashboardSnapshot>(
                "/api/admin/statistics/dashboard-data",
                {
                    headers: {
                        Accept: "application/json",
                    },
                },
            );

            const snapshot = response.data;

            this.setState({
                snapshot,
                loading: false,
                refreshing: false,
            });
        } catch (error) {
            if (handleUnauthenticatedResponse(error as AxiosError)) {
                return;
            }

            this.setState({
                error: "Statistics dashboard data could not be loaded.",
                loading: false,
                refreshing: false,
            });
        }
    }

    subscribeToDashboardUpdates() {
        const echo = (window as any).Echo;

        if (!echo) {
            this.setState({ websocketStatus: "unavailable" });

            return;
        }

        const channel = echo.private(this.channelName);

        channel.listen(
            ".admin.statistics.dashboard.updated",
            (payload: { snapshot: AdminStatisticsDashboardSnapshot }) => {
                if (payload.snapshot) {
                    this.setState({
                        snapshot: payload.snapshot,
                        websocketStatus: "connected",
                        error: "",
                        loading: false,
                    });
                }
            },
        );

        this.setState({ websocketStatus: "connected" });
    }

    renderLastUpdated(): string {
        if (!this.state.snapshot) {
            return "Not loaded";
        }

        return new Date(this.state.snapshot.generated_at).toLocaleString();
    }

    renderLoginParticipation(snapshot: AdminStatisticsDashboardSnapshot) {
        return (
            <section
                className="rounded-sm bg-white p-4 shadow dark:bg-gray-800"
                aria-labelledby="login-participation-heading"
            >
                <h2
                    id="login-participation-heading"
                    className="text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Login Participation
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {snapshot.metric_definitions.login_participation}
                </p>
                <div className="mt-4 overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <caption className="sr-only">
                            Login participation by window
                        </caption>
                        <thead>
                            <tr>
                                <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                    Window
                                </th>
                                <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                    Login Users
                                </th>
                                <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                    Total Users
                                </th>
                                <th className="border-b border-gray-200 py-2 dark:border-gray-700">
                                    Participation
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {snapshot.login_participation_summary.map((row) => (
                                <tr key={row.window}>
                                    <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                        {row.label}
                                    </td>
                                    <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                        {row.distinct_login_users.toLocaleString()}
                                    </td>
                                    <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                        {row.total_users.toLocaleString()}
                                    </td>
                                    <td className="border-b border-gray-100 py-2 dark:border-gray-700">
                                        {row.percentage.toLocaleString()}%
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>
        );
    }

    render() {
        const snapshot = this.state.snapshot;

        return (
            <main className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">
                            Statistics Dashboard
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Database-backed admin metrics updated by websocket
                            events.
                        </p>
                    </div>
                    <button
                        type="button"
                        className="rounded-sm bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        onClick={() => void this.fetchSnapshot()}
                        disabled={this.state.refreshing}
                        aria-label="Refresh statistics dashboard data"
                    >
                        {this.state.refreshing ? "Refreshing" : "Refresh"}
                    </button>
                </div>

                <div className="rounded-sm bg-white p-4 text-sm shadow dark:bg-gray-800">
                    <p aria-live="polite">
                        Last updated: {this.renderLastUpdated()}
                    </p>
                    <p>
                        Websocket:{" "}
                        {this.state.websocketStatus === "connected"
                            ? "connected"
                            : "unavailable"}
                    </p>
                </div>

                {this.state.loading && (
                    <p role="status" aria-live="polite">
                        Loading statistics dashboard data...
                    </p>
                )}

                {this.state.error && (
                    <p
                        className="rounded-sm border border-red-400 bg-red-50 p-3 text-red-800 dark:bg-red-950 dark:text-red-100"
                        role="alert"
                    >
                        {this.state.error}
                    </p>
                )}

                {snapshot && (
                    <>
                        <dl className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <StatCard
                                label="Total Registered Users"
                                value={snapshot.summary.total_registered_users.toLocaleString()}
                                definition={
                                    snapshot.metric_definitions
                                        .total_registered_users
                                }
                            />
                            <StatCard
                                label="Total Characters"
                                value={snapshot.summary.total_characters.toLocaleString()}
                            />
                            <StatCard
                                label="Richest Character"
                                value={
                                    snapshot.summary.richest_character
                                        ? snapshot.summary.richest_character
                                              .name
                                        : "None"
                                }
                                secondaryValue={
                                    snapshot.summary.richest_character
                                        ? `${this.numberFormatter.format(
                                              snapshot.summary.richest_character
                                                  .gold ?? 0,
                                          )} gold`
                                        : undefined
                                }
                                definition={
                                    snapshot.metric_definitions
                                        .richest_character
                                }
                            />
                            <StatCard
                                label="Highest Level Character"
                                value={
                                    snapshot.summary.highest_level_character
                                        ? snapshot.summary
                                              .highest_level_character.name
                                        : "None"
                                }
                                secondaryValue={
                                    snapshot.summary.highest_level_character
                                        ? `Level ${this.numberFormatter.format(
                                              snapshot.summary
                                                  .highest_level_character
                                                  .level ?? 0,
                                          )}`
                                        : undefined
                                }
                                definition={
                                    snapshot.metric_definitions
                                        .highest_level_character
                                }
                            />
                        </dl>

                        <div className="grid gap-4 xl:grid-cols-3">
                            <ChartCard
                                title="New Registrations"
                                description={
                                    snapshot.metric_definitions
                                        .new_registrations
                                }
                                chart={snapshot.registration_chart}
                            />
                            <ChartCard
                                title="Login Activity"
                                description={
                                    snapshot.metric_definitions.login_activity
                                }
                                chart={snapshot.login_chart}
                            />
                            <ChartCard
                                title="Average Login Duration"
                                description={
                                    snapshot.metric_definitions
                                        .average_login_duration
                                }
                                chart={snapshot.login_duration_chart}
                            />
                        </div>

                        <div className="grid gap-4 xl:grid-cols-2">
                            <ChartCard
                                title="Today's Login Count"
                                description={
                                    snapshot.metric_definitions
                                        .today_login_count
                                }
                                chart={snapshot.today_login_count_chart}
                            />
                            {this.renderLoginParticipation(snapshot)}
                        </div>

                        <OnlineCharacterList
                            characters={snapshot.online_characters}
                            definition={
                                snapshot.metric_definitions.online_characters
                            }
                        />

                        <div className="grid gap-4 xl:grid-cols-2">
                            <RankedTable
                                title="Reincarnation Stats"
                                description={
                                    snapshot.metric_definitions
                                        .reincarnation_stats
                                }
                                chart={snapshot.reincarnation_chart}
                                valueLabel="Times Reincarnated"
                            />
                            <RankedTable
                                title="Character Gold Stats"
                                description={
                                    snapshot.metric_definitions
                                        .character_gold_stats
                                }
                                chart={snapshot.gold_chart}
                                valueLabel="Gold"
                            />
                            <RankedTable
                                title="Quest Completion Stats"
                                description={
                                    snapshot.metric_definitions
                                        .quest_completion_stats
                                }
                                chart={snapshot.quest_completion_chart}
                                valueLabel="Completions"
                            />
                            <RankedTable
                                title="Guide Quest Completion Stats"
                                description={
                                    snapshot.metric_definitions
                                        .guide_quest_completion_stats
                                }
                                chart={snapshot.guide_quest_completion_chart}
                                valueLabel="Completions"
                            />
                        </div>

                        <section
                            className="rounded-sm bg-white p-4 shadow dark:bg-gray-800"
                            aria-labelledby="kingdom-summary-heading"
                        >
                            <h2
                                id="kingdom-summary-heading"
                                className="text-lg font-semibold"
                            >
                                Kingdom Summary
                            </h2>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {snapshot.metric_definitions.kingdom_summary}
                            </p>
                            <dl className="mt-4 grid gap-4 sm:grid-cols-3">
                                <StatCard
                                    label="Total Kingdoms"
                                    value={snapshot.kingdom_summary.total_kingdoms.toLocaleString()}
                                />
                                <StatCard
                                    label="Kingdoms With Owners"
                                    value={snapshot.kingdom_summary.kingdoms_with_owners.toLocaleString()}
                                />
                                <StatCard
                                    label="NPC Kingdoms"
                                    value={snapshot.kingdom_summary.npc_kingdoms.toLocaleString()}
                                />
                            </dl>
                            <div className="mt-4 overflow-x-auto">
                                <table className="min-w-full text-left text-sm">
                                    <caption className="sr-only">
                                        Top kingdom holders
                                    </caption>
                                    <thead>
                                        <tr>
                                            <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                                Character
                                            </th>
                                            <th className="border-b border-gray-200 py-2 dark:border-gray-700">
                                                Kingdoms
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {snapshot.top_kingdom_holders.map(
                                            (holder) => (
                                                <tr key={holder.character_name}>
                                                    <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                                        {holder.character_name}
                                                    </td>
                                                    <td className="border-b border-gray-100 py-2 dark:border-gray-700">
                                                        {holder.kingdom_count.toLocaleString()}
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </>
                )}
            </main>
        );
    }
}
