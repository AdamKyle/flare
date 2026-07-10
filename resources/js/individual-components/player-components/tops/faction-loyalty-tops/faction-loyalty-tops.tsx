import React from "react";
import FactionLoyaltyTopsProps from "./types/faction-loyalty-tops-props";
import FactionLoyaltyTopsState from "./types/faction-loyalty-tops-state";
import FactionLoyaltyTopsAjax from "./ajax/faction-loyalty-tops-ajax";
import { factionLoyaltyTopsServiceContainer } from "./container/faction-loyalty-tops-container";
import FactionLoyaltyTopsListener from "./event-listeners/faction-loyalty-tops-listener";
import FactionLoyaltyTopsListenerDefinition from "./event-listeners/faction-loyalty-tops-listener-definition";
import TopsPageShell from "../shared/components/tops-page-shell";
import FactionLoyaltyLeaderboard from "./components/faction-loyalty-leaderboard";
import TopsApiResponse from "../shared/types/tops-api-response";

const resetDescription =
    "This leaderboard tracks live, cumulative character progress and does not reset each month. Current Month and All Time reflect the same up-to-date totals; switching periods changes which characters are eligible to appear, not the underlying data.";

export default class FactionLoyaltyTops extends React.Component<
    FactionLoyaltyTopsProps,
    FactionLoyaltyTopsState
> {
    private ajax: FactionLoyaltyTopsAjax;

    private listener: FactionLoyaltyTopsListenerDefinition;

    constructor(props: FactionLoyaltyTopsProps) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            leaderboard: null,
            period: props.default_period,
            metric: "highest_faction_level",
            search: "",
        };

        this.ajax = factionLoyaltyTopsServiceContainer().fetch(
            FactionLoyaltyTopsAjax,
        );
        this.listener =
            factionLoyaltyTopsServiceContainer().fetch<FactionLoyaltyTopsListenerDefinition>(
                FactionLoyaltyTopsListener,
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
                title="Faction Loyalty Leaderboard"
                description="Ranked by Highest Level Faction, tie-broken by Total Faction Level, NPCs Helped, and Total NPC Fame Level."
                resetDescription={resetDescription}
            >
                <FactionLoyaltyLeaderboard
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
