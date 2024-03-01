import React, { Fragment } from "react";
import Select from "react-select";
import SmallActionsState from "./types/small-actions-state";
import SmallActionsManager from "../../lib/game/actions/small-actions-manager";
import MonsterActions from "./components/small-actions/monster-actions";
import ActionsTimers from "./components/actions-timers";
import SmallCraftingSection from "./components/small-actions/small-crafting-section";
import SmallExplorationSection from "./components/small-actions/small-exploration-section";
import JoinPvp from "./components/join-pvp";
import MapTimer from "../map/map-timer";
import DuelPlayer from "./components/duel-player";
import SmallMapMovementActions from "./components/small-actions/small-map-movement-actions";
import SmallActionsProps from "./types/small-actions-props";
import CelestialFight from "./components/celestial-fight";
import SmallerSpecialtyShop from "./components/small-actions/smaller-specialty-shop";
import { removeCommas } from "../../lib/game/format-number";
import GamblingSection from "./components/gambling-section";
import Revive from "./components/fight-section/revive";
import RaidSection from "./components/raid-section";
import { GameActionState } from "../../lib/game/types/game-state";
import { DateTime } from "luxon";
import { updateTimers } from "../../lib/ajax/update-timers";

export default class SmallerActions extends React.Component<
    SmallActionsProps,
    SmallActionsState
> {
    private attackTimeOut: any;

    private craftingTimeOut: any;

    private mapTimeOut: any;

    private pvpUpdate: any;

    private duelOptions: any;

    private explorationTimeOut: any;

    private smallActionsManager: SmallActionsManager;

    private celestialTimeout: any;

    private manageRankFights: any;

    constructor(props: SmallActionsProps) {
        super(props);

        this.state = {
            selected_action: null,
            monsters: [],
            raid_monsters: [],
            characters_for_dueling: [],
            pvp_characters_on_map: [],
            attack_time_out: 0,
            crafting_time_out: 0,
            automation_time_out: 0,
            celestial_time_out: 0,
            movement_time_left: 0,
            crafting_type: null,
            duel_fight_info: null,
            loading: true,
            show_exploration: false,
            show_celestial_fight: false,
            show_duel_fight: false,
            show_join_pvp: false,
            show_hell_forged_section: false,
            show_purgatory_chains_section: false,
            show_gambling_section: false,
            show_rank_fight: false,
            total_ranks: 0,
        };

        // @ts-ignore
        this.attackTimeOut = Echo.private(
            "show-timeout-bar-" + this.props.character.user_id
        );

        // @ts-ignore
        this.craftingTimeOut = Echo.private(
            "show-crafting-timeout-bar-" + this.props.character.user_id
        );

        // @ts-ignore
        this.mapTimeOut = Echo.private(
            "show-timeout-move-" + this.props.character.user_id
        );

        // @ts-ignore
        this.explorationTimeOut = Echo.private(
            "exploration-timeout-" + this.props.character.user_id
        );

        // @ts-ignore
        this.pvpUpdate = Echo.private(
            "update-pvp-attack-" + this.props.character.user_id
        );

        // @ts-ignore
        this.celestialTimeout = Echo.private(
            "update-character-celestial-timeout-" + this.props.character.user_id
        );

        // @ts-ignore
        this.manageRankFights = Echo.private(
            "update-rank-fight-" + this.props.character.user_id
        );

        // @ts-ignore
        this.duelOptions = Echo.join("update-duel");

        this.smallActionsManager = new SmallActionsManager(this);
    }

    componentDidMount() {
        this.setState(
            {
                ...this.state,
                ...this.props.action_data,
                ...{ loading: false },
            },
            () => {
                updateTimers(this.props.character.id);
            }
        );

        // @ts-ignore
        this.attackTimeOut.listen(
            "Game.Core.Events.ShowTimeOutEvent",
            (event: any) => {
                this.setState({
                    attack_time_out: event.forLength,
                });
            }
        );

        // @ts-ignore
        this.craftingTimeOut.listen(
            "Game.Core.Events.ShowCraftingTimeOutEvent",
            (event: any) => {
                this.setState({
                    crafting_time_out: event.timeout,
                });
            }
        );

        // @ts-ignore
        this.manageRankFights.listen(
            "Game.Maps.Events.UpdateRankFights",
            (event: any) => {
                this.setState({
                    show_rank_fight: event.showRankSelection,
                    total_ranks: event.ranks,
                });
            }
        );

        // @ts-ignore
        this.mapTimeOut.listen(
            "Game.Maps.Events.ShowTimeOutEvent",
            (event: any) => {
                this.setState({
                    movement_time_left: event.forLength,
                });
            }
        );

        // @ts-ignore
        this.celestialTimeout.listen(
            "Game.Core.Events.UpdateCharacterCelestialTimeOut",
            (event: any) => {
                this.setState({
                    celestial_time_out: event.timeOut,
                });
            }
        );

        // // @ts-ignore
        this.duelOptions.listen(
            "Game.Maps.Events.UpdateDuelAtPosition",
            (event: any) => {
                this.setState(
                    {
                        pvp_characters_on_map: event.characters,
                        characters_for_dueling: [],
                    },
                    () => {
                        const characterLevel = removeCommas(
                            this.props.character.level
                        );

                        if (characterLevel >= 301) {
                            this.smallActionsManager.setCharactersForDueling(
                                event.characters
                            );
                        }
                    }
                );
            }
        );

        // @ts-ignore
        this.pvpUpdate.listen(
            "Game.Battle.Events.UpdateCharacterPvpAttack",
            (event: any) => {
                this.setState({
                    show_duel_fight: true,
                    duel_fight_info: event.data,
                });
            }
        );

        // // @ts-ignore
        this.explorationTimeOut.listen(
            "Game.Exploration.Events.ExplorationTimeOut",
            (event: any) => {
                this.setState({
                    automation_time_out: event.forLength,
                });
            }
        );
    }

    componentDidUpdate(
        prevProps: Readonly<SmallActionsProps>,
        prevState: Readonly<SmallActionsState>,
        snapshot?: any
    ): void {
        if (
            this.props.action_data !== null &&
            this.state.monsters.length === 0
        ) {
            this.setState(
                {
                    ...this.state,
                    ...this.props.action_data,
                    ...{ loading: false },
                },
                () => {
                    this.props.update_parent_state({
                        monsters: this.state.monsters,
                        raid_monsters: this.state.raid_monsters,
                    });
                }
            );
        }

        if (this.props.action_data === null) {
            return;
        }

        if (this.props.action_data.monsters != this.state.monsters) {
            this.setState({
                monsters: this.props.action_data.monsters,
            });
        }

        if (this.props.action_data.raid_monsters != this.state.raid_monsters) {
            this.setState({
                raid_monsters: this.props.action_data.raid_monsters,
            });
        }
    }

    componentWillUnmount(): void {
        this.props.update_parent_state({
            monsters: this.state.monsters,
            raid_monsters: this.state.raid_monsters,
        });
    }

    setUpState(): void {
        if (this.props.action_data === null) {
            return;
        }

        let actionData: GameActionState = this.props.action_data;

        this.setState({ ...this.state, ...actionData, ...{ loading: false } });
    }

    showAction(data: any) {
        this.smallActionsManager.setSelectedAction(data);
    }

    updateAttackTimer(timeLeft: number) {
        this.setState({
            attack_time_out: timeLeft,
        });
    }

    updateCraftingTimer(timeLeft: number) {
        this.setState({
            crafting_time_out: timeLeft,
        });
    }

    closeMonsterSection() {
        this.setState({
            selected_action: null,
        });
    }

    closeCraftingSection() {
        this.setState({
            selected_action: null,
        });
    }

    closeMapSection() {
        this.setState({
            selected_action: null,
        });
    }

    closeExplorationSection() {
        this.setState({
            selected_action: null,
        });
    }

    closeFightCelestialSection() {
        this.setState({
            selected_action: null,
        });
    }

    manageDuel() {
        this.setState({
            selected_action: null,
            show_duel_fight: !this.state.show_duel_fight,
        });
    }

    manageJoinPvp() {
        this.setState({
            selected_action: null,
            show_join_pvp: !this.state.show_join_pvp,
        });
    }

    manageHellForgedShop() {
        this.setState({
            selected_action: null,
        });
    }

    managePurgatoryChainShop() {
        this.setState({
            selected_action: null,
        });
    }

    removeSlots() {
        this.setState({
            selected_action: null,
        });
    }

    resetDuelData() {
        this.setState({
            duel_fight_info: null,
        });
    }

    createMonster() {
        if (this.state.raid_monsters.length > 0) {
            return (
                <RaidSection
                    raid_monsters={this.state.raid_monsters}
                    character_id={this.props.character.id}
                    can_attack={this.props.character.can_attack}
                    is_dead={this.props.character.is_dead}
                    is_small={false}
                    character_name={this.props.character.name}
                    user_id={this.props.character.user_id}
                    character_current_health={this.props.character.health}
                />
            );
        }

        return (
            <MonsterActions
                monsters={this.state.monsters}
                character={this.props.character}
                is_rank_fights={this.state.show_rank_fight}
                total_ranks={this.state.total_ranks}
                close_monster_section={this.closeMonsterSection.bind(this)}
                character_statuses={this.props.character_status}
                is_small={true}
            />
        );
    }

    showCrafting() {
        return (
            <SmallCraftingSection
                close_crafting_section={this.closeCraftingSection.bind(this)}
                character={this.props.character}
                character_status={this.props.character_status}
                crafting_time_out={this.state.crafting_time_out}
            />
        );
    }

    renderExploration() {
        return (
            <SmallExplorationSection
                close_exploration_section={this.closeExplorationSection.bind(
                    this
                )}
                character={this.props.character}
                monsters={this.state.monsters}
            />
        );
    }

    showMapMovement() {
        return (
            <SmallMapMovementActions
                close_map_section={this.closeMapSection.bind(this)}
                update_celestial={(id: number | null) => {}}
                view_port={this.props.view_port}
                character={this.props.character}
                character_currencies={this.props.character_currencies}
                update_plane_quests={this.props.update_plane_quests}
                update_character_position={this.props.update_character_position}
                map_data={this.props.map_data}
                set_map_data={this.props.set_map_data}
            />
        );
    }

    showCelestialFight() {
        return (
            <CelestialFight
                character={this.props.character}
                manage_celestial_fight={this.closeFightCelestialSection.bind(
                    this
                )}
                celestial_id={this.props.celestial_id}
                update_celestial={this.props.update_celestial}
            />
        );
    }

    showDuelFight() {
        return (
            <DuelPlayer
                characters={this.state.characters_for_dueling}
                duel_data={this.state.duel_fight_info}
                character={this.props.character}
                manage_pvp={this.manageDuel.bind(this)}
                reset_duel_data={this.resetDuelData.bind(this)}
                is_small={true}
            />
        );
    }

    showSlots() {
        return (
            <GamblingSection
                character={this.props.character}
                close_gambling_section={this.removeSlots.bind(this)}
                is_small={true}
            />
        );
    }

    showJoinPVP() {
        return (
            <JoinPvp
                manage_section={this.manageJoinPvp.bind(this)}
                character_id={this.props.character.id}
            />
        );
    }

    showSpecialtyShop(type: string) {
        return (
            <SmallerSpecialtyShop
                show_hell_forged_section={type === "hell-forged-gear"}
                character={this.props.character}
                manage_hell_forged_shop={this.manageHellForgedShop.bind(this)}
                manage_purgatory_chain_shop={this.managePurgatoryChainShop.bind(
                    this
                )}
            />
        );
    }

    buildSection() {
        switch (this.state.selected_action) {
            case "fight":
                return this.createMonster();
            case "explore":
                return this.renderExploration();
            case "craft":
                return this.showCrafting();
            case "map-movement":
                return this.showMapMovement();
            case "celestial-fight":
                return this.showCelestialFight();
            case "pvp-fight":
                return this.showDuelFight();
            case "join-monthly-pvp":
                return this.showJoinPVP();
            case "hell-forged-gear":
                return this.showSpecialtyShop("hell-forged-gear");
            case "purgatory-chains-gear":
                return this.showSpecialtyShop("purgatory-chains-gear");
            case "slots":
                return this.showSlots();
            default:
                return null;
        }
    }

    render() {
        return (
            <Fragment>
                {this.state.selected_action !== null ? (
                    this.buildSection()
                ) : (
                    <Fragment>
                        <Select
                            onChange={this.showAction.bind(this)}
                            options={this.smallActionsManager.buildOptions()}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base: any) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
                            menuPortalTarget={document.body}
                            value={this.smallActionsManager.defaultSelectedAction()}
                        />
                        <Revive
                            can_attack={this.props.character_status.can_attack}
                            is_character_dead={this.props.character.is_dead}
                            character_id={this.props.character.id}
                        />
                    </Fragment>
                )}

                <div className="pb-5 relative bottom-[40px]">
                    <ActionsTimers
                        attack_time_out={this.state.attack_time_out}
                        crafting_time_out={this.state.crafting_time_out}
                        update_attack_timer={this.updateAttackTimer.bind(this)}
                        update_crafting_timer={this.updateCraftingTimer.bind(
                            this
                        )}
                    />
                </div>
                <div className="mt-4">
                    <div className="relative">
                        <div className="absolute bottom-0 inset-x-0">
                            <MapTimer
                                time_left={this.state.movement_time_left}
                                automation_time_out={this.state.automation_time_out}
                                celestial_time_out={this.state.celestial_time_out}
                            />
                        </div>
                    </div>
                </div>
            </Fragment>
        );
    }
}
