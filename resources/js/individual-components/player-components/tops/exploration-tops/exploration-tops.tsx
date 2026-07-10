import React from "react";
import ExplorationTopsProps from "./types/exploration-tops-props";
import ExplorationTopsState from "./types/exploration-tops-state";
import ExplorationTopsAjax from "./ajax/exploration-tops-ajax";
import { explorationTopsServiceContainer } from "./container/exploration-tops-container";
import ExplorationTopsListener from "./event-listeners/exploration-tops-listener";
import ExplorationTopsListenerDefinition from "./event-listeners/exploration-tops-listener-definition";
import TopsPageShell from "../shared/components/tops-page-shell";
import ExplorationLeaderboard from "./components/exploration-leaderboard";
import TopsApiResponse from "../shared/types/tops-api-response";

const resetDescription =
    "This leaderboard tracks live, cumulative character progress and does not reset each month. Current Month and All Time reflect the same up-to-date totals; switching periods changes which characters are eligible to appear, not the underlying data.";

export default class ExplorationTops extends React.Component<
    ExplorationTopsProps,
    ExplorationTopsState
> {
    private ajax: ExplorationTopsAjax;

    private listener: ExplorationTopsListenerDefinition;

    constructor(props: ExplorationTopsProps) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            leaderboard: null,
            period: props.default_period,
            metric: "kills",
            search: "",
        };

        this.ajax =
            explorationTopsServiceContainer().fetch(ExplorationTopsAjax);
        this.listener =
            explorationTopsServiceContainer().fetch<ExplorationTopsListenerDefinition>(
                ExplorationTopsListener,
            );

        this.listener.initialize(this);
        this.listener.register();
    }

    componentDidMount() {
        this.fetchLeaderboard();
        this.listener.listen();
    }

    fetchLeaderboard() {
        this.setState({
            loading: true,
        });

        this.ajax.fetchLeaderboard(
            {
                period: this.state.period,
                metric: this.state.metric,
                search: this.state.search,
            },
            (data: TopsApiResponse) => this.applyLeaderboard(data),
            (message: string) => this.setErrorMessage(message),
        );
    }

    applyLeaderboard(data: TopsApiResponse) {
        this.setState({
            loading: false,
            leaderboard: data,
            error_message: null,
        });
    }

    setErrorMessage(message: string) {
        this.setState({
            loading: false,
            error_message: message,
        });
    }

    changePeriod(value: string) {
        this.setState(
            {
                period: value,
            },
            () => this.fetchLeaderboard(),
        );
    }

    changeMetric(value: string) {
        this.setState(
            {
                metric: value,
            },
            () => this.fetchLeaderboard(),
        );
    }

    search(value: string) {
        this.setState(
            {
                search: value,
            },
            () => this.fetchLeaderboard(),
        );
    }

    render() {
        return (
            <TopsPageShell
                title="Exploration Leaderboard"
                description="The most active explorers, ranked by kills, fights, XP, skill XP, and runs."
                resetDescription={resetDescription}
            >
                <ExplorationLeaderboard
                    leaderboard={this.state.leaderboard}
                    loading={this.state.loading}
                    errorMessage={this.state.error_message}
                    period={this.state.period}
                    metric={this.state.metric}
                    search={this.state.search}
                    onPeriodChange={(value: string) => this.changePeriod(value)}
                    onMetricChange={(value: string) => this.changeMetric(value)}
                    onSearch={(value: string) => this.search(value)}
                />
            </TopsPageShell>
        );
    }
}
