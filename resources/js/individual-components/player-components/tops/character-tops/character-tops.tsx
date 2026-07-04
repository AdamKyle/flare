import React from "react";
import CharacterTopsProps from "./types/character-tops-props";
import CharacterTopsState from "./types/character-tops-state";
import CharacterTopsAjax from "./ajax/character-tops-ajax";
import { characterTopsServiceContainer } from "./container/character-tops-container";
import CharacterTopsListener from "./event-listeners/character-tops-listener";
import CharacterTopsListenerDefinition from "./event-listeners/character-tops-listener-definition";
import TopsPageShell from "../shared/components/tops-page-shell";
import TopsLoadingState from "../shared/components/tops-loading-state";
import CharacterLeaderboard from "./components/character-leaderboard";
import CharacterProfile from "./components/character-profile";
import TopsApiResponse from "../shared/types/tops-api-response";
import CharacterProfileType from "./types/character-profile";

const resetDescription =
    "Leaderboards reset on the first day of each calendar month. Current Month is shown by default, previous monthly results stay available through archived snapshots, and All Time keeps lifetime totals.";

export default class CharacterTops extends React.Component<
    CharacterTopsProps,
    CharacterTopsState
> {
    private ajax: CharacterTopsAjax;

    private listener: CharacterTopsListenerDefinition;

    constructor(props: CharacterTopsProps) {
        super(props);

        this.state = {
            loading: true,
            profile_loading: props.selected_character_id !== null,
            error_message: null,
            leaderboard: null,
            profile: null,
            active_profile_tab: "overview",
            period: props.default_period,
            metric: "progression",
            search: "",
            online_only: false,
        };

        this.ajax = characterTopsServiceContainer().fetch(CharacterTopsAjax);
        this.listener =
            characterTopsServiceContainer().fetch<CharacterTopsListenerDefinition>(
                CharacterTopsListener,
            );

        this.listener.initialize(this, this.props.selected_character_id);
        this.listener.register();
    }

    componentDidMount() {
        this.fetchLeaderboard();

        if (this.props.selected_character_id !== null) {
            this.fetchProfile(this.props.selected_character_id);
        }

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
                online_only: this.state.online_only ? "1" : "0",
            },
            (data: TopsApiResponse) => this.applyLeaderboard(data),
            (message: string) => this.setErrorMessage(message),
        );
    }

    fetchProfile(characterId: number) {
        this.setState({
            profile_loading: true,
        });

        this.ajax.fetchProfile(
            characterId,
            (data: CharacterProfileType) => this.applyProfile(data),
            (message: string) => this.setProfileErrorMessage(message),
        );
    }

    applyLeaderboard(data: TopsApiResponse) {
        this.setState({
            loading: false,
            leaderboard: data,
            error_message: null,
        });
    }

    applyProfile(data: CharacterProfileType) {
        this.setState({
            profile_loading: false,
            profile: data,
            error_message: null,
        });
    }

    setErrorMessage(message: string) {
        this.setState({
            loading: false,
            error_message: message,
        });
    }

    setProfileErrorMessage(message: string) {
        this.setState({
            profile_loading: false,
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

    changeOnlineOnly(value: boolean) {
        this.setState(
            {
                online_only: value,
            },
            () => this.fetchLeaderboard(),
        );
    }

    changeProfileTab(tab: string) {
        this.setState({
            active_profile_tab: tab,
        });
    }

    renderProfile() {
        if (this.state.profile_loading || this.state.profile === null) {
            return <TopsLoadingState />;
        }

        return (
            <CharacterProfile
                profile={this.state.profile}
                activeTab={this.state.active_profile_tab}
                onTabChange={(tab: string) => this.changeProfileTab(tab)}
            />
        );
    }

    renderLeaderboard() {
        return (
            <CharacterLeaderboard
                leaderboard={this.state.leaderboard}
                loading={this.state.loading}
                errorMessage={this.state.error_message}
                period={this.state.period}
                metric={this.state.metric}
                search={this.state.search}
                onlineOnly={this.state.online_only}
                onPeriodChange={(value: string) => this.changePeriod(value)}
                onMetricChange={(value: string) => this.changeMetric(value)}
                onSearch={(value: string) => this.search(value)}
                onOnlineOnly={(value: boolean) => this.changeOnlineOnly(value)}
            />
        );
    }

    render() {
        const isProfile = this.props.selected_character_id !== null;

        return (
            <TopsPageShell
                title={
                    isProfile ? "Character Inspect" : "Character Progression"
                }
                description={
                    isProfile
                        ? "A read-only public inspect view built from verified game progression data."
                        : "The highest progressing characters, ranked by reincarnations, level, XP, and gold."
                }
                resetDescription={isProfile ? undefined : resetDescription}
            >
                {isProfile ? this.renderProfile() : this.renderLeaderboard()}
            </TopsPageShell>
        );
    }
}
