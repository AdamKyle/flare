import React from "react";
import CharacterTopsProps from "./types/character-tops-props";
import CharacterTopsState from "./types/character-tops-state";
import CharacterTopsAjax from "./ajax/character-tops-ajax";
import { characterTopsServiceContainer } from "./container/character-tops-container";
import CharacterTopsListener from "./event-listeners/character-tops-listener";
import CharacterTopsListenerDefinition from "./event-listeners/character-tops-listener-definition";
import TopsPageShell from "../shared/components/tops-page-shell";
import TopsLoadingState from "../shared/components/tops-loading-state";
import TopsErrorState from "../shared/components/tops-error-state";
import CharacterLeaderboard from "./components/character-leaderboard";
import CharacterProfile from "./components/character-profile";
import TopsApiResponse from "../shared/types/tops-api-response";
import CharacterProfileType, {
    CharacterOverview,
} from "./types/character-profile";
import TopsValue from "../shared/types/tops-value";

const resetDescription =
    "This leaderboard tracks live, cumulative character progress and does not reset each month. Current Month and All Time reflect the same up-to-date totals; switching periods changes which characters are eligible to appear, not the underlying data.";

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
            deferred_loading: {
                activity: false,
                quests: false,
                kingdoms: false,
                analytics: false,
            },
            deferred_errors: {
                activity: null,
                quests: null,
                kingdoms: null,
                analytics: null,
            },
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
        if (this.props.selected_character_id === null) {
            this.fetchLeaderboard();
        } else {
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
            error_message: null,
        });

        Promise.all([
            this.ajax.fetchOverview(characterId),
            this.ajax.fetchStats(characterId),
            this.ajax.fetchEquipment(characterId),
            this.ajax.fetchSkills(characterId),
            this.ajax.fetchFactions(characterId),
            this.ajax.fetchReincarnation(characterId),
        ])
            .then(
                ([
                    overview,
                    stats,
                    equipment,
                    skills,
                    factions,
                    reincarnation,
                ]) => {
                    const profile = this.assembleCoreProfile(
                        overview,
                        stats,
                        equipment,
                        skills,
                        factions,
                        reincarnation,
                    );

                    this.setState(
                        {
                            profile_loading: false,
                            profile,
                            error_message: null,
                        },
                        () => this.fetchDeferredSections(characterId),
                    );
                },
            )
            .catch((message: unknown) => {
                this.setProfileErrorMessage(
                    typeof message === "string"
                        ? message
                        : "Unable to load Character Profile.",
                );
            });
    }

    fetchDeferredSections(characterId: number) {
        this.setState({
            deferred_loading: {
                activity: true,
                quests: true,
                kingdoms: true,
                analytics: true,
            },
            deferred_errors: {
                activity: null,
                quests: null,
                kingdoms: null,
                analytics: null,
            },
        });

        this.ajax
            .fetchActivity(characterId)
            .then((activity) => this.applyActivity(activity))
            .catch((message: unknown) =>
                this.setDeferredError("activity", message),
            );

        this.ajax
            .fetchQuests(characterId)
            .then((quests) => this.applyQuests(quests))
            .catch((message: unknown) =>
                this.setDeferredError("quests", message),
            );

        this.ajax
            .fetchKingdoms(characterId)
            .then((kingdoms) => this.applyKingdoms(kingdoms))
            .catch((message: unknown) =>
                this.setDeferredError("kingdoms", message),
            );

        this.ajax
            .fetchAnalytics(characterId)
            .then((analytics) => this.applyAnalytics(analytics))
            .catch((message: unknown) =>
                this.setDeferredError("analytics", message),
            );
    }

    assembleCoreProfile(
        overview: CharacterOverview,
        stats: Record<string, TopsValue>,
        equipment: CharacterProfileType["equipment"],
        skills: Record<string, TopsValue>,
        factions: Record<string, TopsValue>,
        reincarnation: Record<string, TopsValue>,
    ): CharacterProfileType {
        const moddedStats =
            (stats.modded_stats as Record<string, TopsValue>) ?? {};

        const summary = {
            gold: overview.gold,
            gold_dust: overview.gold_dust,
            shards: overview.shards,
            copper_coins: overview.copper_coins,
            inventory_count: overview.inventory_count,
            inventory_max: overview.inventory_max,
            alchemy_bag_count: overview.alchemy_bag_count,
            gem_bag_count: overview.gem_bag_count,
            damage_stat: overview.damage_stat,
            to_hit: stats.to_hit,
            class_bonus: overview.class ?? null,
            fight_timeout: null,
            movement_timeout: null,
            reincarnation: reincarnation,
        };

        const info = {
            name: overview.name,
            race: overview.race,
            class: overview.class,
            level: overview.level,
            max_health: moddedStats.dur ?? null,
            total_attack: stats.weapon_damage,
            heal_for: stats.healing,
            ac: stats.ac,
            xp: overview.xp,
            xp_next: overview.xp_next,
        };

        return {
            overview,
            summary,
            info,
            stats,
            equipment,
            skills: {
                regular_skills:
                    (skills.regular_skills as unknown as CharacterProfileType["skills"]["regular_skills"]) ??
                    [],
                crafting_skills:
                    (skills.crafting_skills as unknown as CharacterProfileType["skills"]["crafting_skills"]) ??
                    [],
            },
            kingdom_passives:
                (skills.kingdom_passives as unknown as CharacterProfileType["kingdom_passives"]) ??
                [],
            class_ranks:
                (skills.class_ranks as unknown as CharacterProfileType["class_ranks"]) ??
                [],
            class_ranks_offered:
                (skills.class_ranks_offered as unknown as CharacterProfileType["class_ranks_offered"]) ??
                [],
            class_rank_specialties:
                skills.class_rank_specialties as unknown as CharacterProfileType["class_rank_specialties"],
            factions,
            reincarnation,
            activity: {},
            quests: {},
            kingdoms: {},
            analytics: {},
        } as unknown as CharacterProfileType;
    }

    applyLeaderboard(data: TopsApiResponse) {
        this.setState({
            loading: false,
            error_message: null,
            leaderboard: data,
        });
    }

    applyActivity(activity: CharacterProfileType["activity"]) {
        if (this.state.profile === null) {
            return;
        }

        this.setState({
            profile: { ...this.state.profile, activity },
            deferred_loading: {
                ...this.state.deferred_loading,
                activity: false,
            },
        });
    }

    applyQuests(quests: CharacterProfileType["quests"]) {
        if (this.state.profile === null) {
            return;
        }

        this.setState({
            profile: { ...this.state.profile, quests },
            deferred_loading: { ...this.state.deferred_loading, quests: false },
        });
    }

    applyKingdoms(kingdoms: CharacterProfileType["kingdoms"]) {
        if (this.state.profile === null) {
            return;
        }

        this.setState({
            profile: { ...this.state.profile, kingdoms },
            deferred_loading: {
                ...this.state.deferred_loading,
                kingdoms: false,
            },
        });
    }

    applyAnalytics(analytics: CharacterProfileType["analytics"]) {
        if (this.state.profile === null) {
            return;
        }

        this.setState({
            profile: { ...this.state.profile, analytics },
            deferred_loading: {
                ...this.state.deferred_loading,
                analytics: false,
            },
        });
    }

    setDeferredError(
        section: "activity" | "quests" | "kingdoms" | "analytics",
        message: unknown,
    ) {
        this.setState({
            deferred_loading: {
                ...this.state.deferred_loading,
                [section]: false,
            },
            deferred_errors: {
                ...this.state.deferred_errors,
                [section]:
                    typeof message === "string"
                        ? message
                        : "Unable to load this section.",
            },
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
        if (this.state.profile_loading) {
            return <TopsLoadingState label="Loading Character Profile" />;
        }

        if (this.state.error_message !== null) {
            return <TopsErrorState message={this.state.error_message} />;
        }

        if (this.state.profile === null) {
            return (
                <TopsErrorState message="Unable to load Character Profile." />
            );
        }

        return (
            <CharacterProfile
                profile={this.state.profile}
                activeTab={this.state.active_profile_tab}
                onTabChange={(tab: string) => this.changeProfileTab(tab)}
                deferred_loading={this.state.deferred_loading}
                deferred_errors={this.state.deferred_errors}
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
                    isProfile ? "Character Profile" : "Character Progression"
                }
                description={
                    isProfile
                        ? "A read-only public character sheet and progression profile."
                        : "The highest progressing characters, ranked by reincarnations, level, XP, and gold."
                }
                resetDescription={isProfile ? undefined : resetDescription}
            >
                {isProfile ? this.renderProfile() : this.renderLeaderboard()}
            </TopsPageShell>
        );
    }
}
