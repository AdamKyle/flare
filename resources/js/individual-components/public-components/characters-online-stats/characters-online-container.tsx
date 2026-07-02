import React from "react";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import LoginDurationChart from "./components/login-duration-chart";
import CharactersOnlineList from "./components/characters-online-list";
import CharactersOnlineProps from "./types/characters-online-props";
import LoginStatistics from "./components/login-statistics";
import RegistrationStatistics from "./components/registration-statistics";
import { CharacterOnlineData } from "./deffinitions/character-online-data";

interface PublicSummary {
    today: number;
    last_hour: number;
    last_month: number;
    last_year: number;
}

interface WhosPlayingSnapshot {
    characters_online: CharacterOnlineData[];
    signup_summary: PublicSummary;
    login_summary: PublicSummary;
}

interface CharactersOnlineContainerState {
    snapshot: WhosPlayingSnapshot | null;
    loading: boolean;
    error_message: string;
    websocket_connected: boolean;
}

export default class CharactersOnlineContainer extends React.Component<
    CharactersOnlineProps,
    CharactersOnlineContainerState
> {
    constructor(props: CharactersOnlineProps) {
        super(props);

        this.state = {
            snapshot: null,
            loading: true,
            error_message: "",
            websocket_connected: false,
        };
    }

    componentDidMount() {
        void this.fetchSnapshot();
        this.subscribeToUpdates();
    }

    componentWillUnmount() {
        const echo = (window as any).Echo;

        if (echo) {
            echo.leave("whos-playing-statistics");
        }
    }

    async fetchSnapshot() {
        try {
            const response = await fetch("/api/whos-playing-statistics", {
                headers: {
                    Accept: "application/json",
                },
            });

            if (!response.ok) {
                throw new Error("Public statistics request failed.");
            }

            const snapshot = await response.json();

            this.setState({
                snapshot,
                loading: false,
                error_message: "",
            });
        } catch {
            this.setState({
                loading: false,
                error_message: "Statistics could not be loaded.",
            });
        }
    }

    subscribeToUpdates() {
        const echo = (window as any).Echo;

        if (!echo) {
            return;
        }

        echo.channel("whos-playing-statistics").listen(
            ".whos.playing.statistics.updated",
            (payload: { snapshot: WhosPlayingSnapshot }) => {
                if (payload.snapshot) {
                    this.setState({
                        snapshot: payload.snapshot,
                        websocket_connected: true,
                        error_message: "",
                    });
                }
            },
        );

        this.setState({ websocket_connected: true });
    }

    renderSummaryCard(label: string, value: number) {
        return (
            <BasicCard>
                <div className="text-sm font-semibold text-gray-600 dark:text-gray-300">
                    {label}
                </div>
                <div className="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {value.toLocaleString()}
                </div>
            </BasicCard>
        );
    }

    render() {
        const snapshot = this.state.snapshot;

        return (
            <div className="pb-10">
                {this.state.error_message ? (
                    <div
                        role="alert"
                        className="mb-4 rounded-sm border border-red-400 bg-red-50 p-3 text-red-800 dark:bg-red-950 dark:text-red-100"
                    >
                        {this.state.error_message}
                    </div>
                ) : null}

                {snapshot ? (
                    <div className="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {this.renderSummaryCard(
                            "Currently Online",
                            snapshot.characters_online.length,
                        )}
                        {this.renderSummaryCard(
                            "Signups Today",
                            snapshot.signup_summary.today,
                        )}
                        {this.renderSummaryCard(
                            "Logins Today",
                            snapshot.login_summary.today,
                        )}
                        {this.renderSummaryCard(
                            "Signups Last Hour",
                            snapshot.signup_summary.last_hour,
                        )}
                        {this.renderSummaryCard(
                            "Logins Last Hour",
                            snapshot.login_summary.last_hour,
                        )}
                        {this.renderSummaryCard(
                            "Signups Last Month",
                            snapshot.signup_summary.last_month,
                        )}
                        {this.renderSummaryCard(
                            "Logins Last Month",
                            snapshot.login_summary.last_month,
                        )}
                        {this.renderSummaryCard(
                            "Signups Last Year",
                            snapshot.signup_summary.last_year,
                        )}
                        {this.renderSummaryCard(
                            "Logins Last Year",
                            snapshot.login_summary.last_year,
                        )}
                    </div>
                ) : null}

                <p className="sr-only" aria-live="polite">
                    Websocket updates are{" "}
                    {this.state.websocket_connected
                        ? "connected"
                        : "unavailable"}
                    .
                </p>

                <div className="mb-5 grid gap-4 lg:grid-cols-2">
                    <BasicCard>
                        <h3 className="mb-2 text-lg font-semibold">
                            Who's Online?
                        </h3>
                        <CharactersOnlineList
                            initialCharactersOnline={
                                snapshot?.characters_online ?? []
                            }
                            initialLoading={this.state.loading}
                        />
                    </BasicCard>
                    <BasicCard>
                        <h3 className="mb-2 text-lg font-semibold">
                            Average Login Duration
                        </h3>
                        <LoginDurationChart />
                    </BasicCard>
                    <BasicCard>
                        <h3 className="mb-2 text-lg font-semibold">
                            Login Activity
                        </h3>
                        <LoginStatistics />
                    </BasicCard>
                    <BasicCard>
                        <h3 className="mb-2 text-lg font-semibold">
                            Registrations
                        </h3>
                        <RegistrationStatistics />
                    </BasicCard>
                </div>
            </div>
        );
    }
}
