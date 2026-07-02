import React from "react";
import KingdomTopsProps from "./types/kingdom-tops-props";
import KingdomTopsState from "./types/kingdom-tops-state";
import KingdomTopsAjax from "./ajax/kingdom-tops-ajax";
import { kingdomTopsServiceContainer } from "./container/kingdom-tops-container";
import KingdomTopsListener from "./event-listeners/kingdom-tops-listener";
import KingdomTopsListenerDefinition from "./event-listeners/kingdom-tops-listener-definition";
import TopsPageShell from "../shared/components/tops-page-shell";
import KingdomLeaderboard from "./components/kingdom-leaderboard";
import TopsApiResponse from "../shared/types/tops-api-response";

const resetDescription =
    "Leaderboards reset on the first day of each calendar month. Current Month is shown by default, previous monthly results stay available through archived snapshots, and All Time keeps lifetime totals.";

export default class KingdomTops extends React.Component<
    KingdomTopsProps,
    KingdomTopsState
> {
    private ajax: KingdomTopsAjax;

    private listener: KingdomTopsListenerDefinition;

    constructor(props: KingdomTopsProps) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            leaderboard: null,
            period: props.default_period,
            metric: "total_value",
            search: "",
        };

        this.ajax = kingdomTopsServiceContainer().fetch(KingdomTopsAjax);
        this.listener =
            kingdomTopsServiceContainer().fetch<KingdomTopsListenerDefinition>(
                KingdomTopsListener,
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
                title="Kingdom Leaderboard"
                description="The strongest kingdoms and rulers, ranked by kingdoms, treasury, gold bars, population, resources, and units."
                resetDescription={resetDescription}
            >
                <KingdomLeaderboard
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
