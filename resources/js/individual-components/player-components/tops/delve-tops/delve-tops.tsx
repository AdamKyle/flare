import React from "react";
import DelveTopsProps from "./types/delve-tops-props";
import DelveTopsState from "./types/delve-tops-state";
import DelveTopsAjax from "./ajax/delve-tops-ajax";
import { delveTopsServiceContainer } from "./container/delve-tops-container";
import DelveTopsListener from "./event-listeners/delve-tops-listener";
import DelveTopsListenerDefinition from "./event-listeners/delve-tops-listener-definition";
import TopsPageShell from "../shared/components/tops-page-shell";
import DelveLeaderboard from "./components/delve-leaderboard";
import TopsApiResponse from "../shared/types/tops-api-response";

const resetDescription =
    "Leaderboards reset on the first day of each calendar month. Current Month is shown by default, previous monthly results stay available through archived snapshots, and All Time keeps lifetime totals.";

export default class DelveTops extends React.Component<
    DelveTopsProps,
    DelveTopsState
> {
    private ajax: DelveTopsAjax;

    private listener: DelveTopsListenerDefinition;

    constructor(props: DelveTopsProps) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            leaderboard: null,
            period: props.default_period,
            metric: "survived_duration_seconds",
            search: "",
        };

        this.ajax = delveTopsServiceContainer().fetch(DelveTopsAjax);
        this.listener =
            delveTopsServiceContainer().fetch<DelveTopsListenerDefinition>(
                DelveTopsListener,
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
                title="Delve Leaderboard"
                description="Ranked by survived duration."
                resetDescription={resetDescription}
            >
                <DelveLeaderboard
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
