import React from "react";
import ActionsManager from "../../lib/game/actions/actions-manager";
import ComponentLoading from "../../components/ui/loading/component-loading";
import MonsterActions from "./components/small-actions/monster-actions";
import ActionsTimers from "./components/actions-timers";
import ActionsProps from "./types/actions-props";
import ActionsState from "./types/actions-state";
import DropDown from "../../components/ui/drop-down/drop-down";
import { CraftingOptions } from "../../lib/game/types/actions/crafting-type-options";
import CraftingSection from "./components/crafting-section";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import ExplorationSection from "./components/exploration-section";
import DuelPlayer from "./components/duel-player";
import SkyOutlineButton from "../../components/ui/buttons/sky-outline-button";
import JoinPvp from "./components/join-pvp";
import CelestialFight from "./components/celestial-fight";
import Shop from "./components/specialty-shops/shop";
import { removeCommas } from "../../lib/game/format-number";
import GamblingSection from "./components/gambling-section";
import RaidSection from "./components/raid-section";
import { GameActionState } from "../../lib/game/types/game-state";
import { updateTimers } from "../../lib/ajax/update-timers";

export default class Actions extends React.Component<
    ActionsProps,
    ActionsState
> {
    private actionsManager: ActionsManager;

    private attackTimeOut: any;

    private craftingTimeOut: any;

    private pvpUpdate: any;

    private duelOptions: any;

    private traverseUpdate: any;

    private manageRankFights: any;

    constructor(props: ActionsProps) {
        super(props);

        this.state = {
            monsters: [],
            raid_monsters: [],
            characters_for_dueling: [],
            pvp_characters_on_map: [],
            attack_time_out: 0,
            crafting_time_out: 0,
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

        this.actionsManager = new ActionsManager(this);

        // @ts-ignore
        this.attackTimeOut = Echo.private(
            "show-timeout-bar-" + this.props.character.user_id
        );

        // @ts-ignore
        this.craftingTimeOut = Echo.private(
            "show-crafting-timeout-bar-" + this.props.character.user_id
        );

        // @ts-ignore
        this.pvpUpdate = Echo.private(
            "update-pvp-attack-" + this.props.character.user_id
        );

        // @ts-ignore
        this.traverseUpdate = Echo.private(
            "update-plane-" + this.props.character.user_id
        );

        // @ts-ignore
        this.manageRankFights = Echo.private(
            "update-rank-fight-" + this.props.character.user_id
        );

        // @ts-ignore
        this.duelOptions = Echo.join("update-duel");
    }

    componentDidMount() {
        this.setUpState();

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
                            this.actionsManager.setCharactersForDueling(
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

        // @ts-ignore
        this.traverseUpdate.listen(
            "Game.Maps.Events.UpdateMap",
            (event: any) => {
                let craftingType = this.state.crafting_type;

                if (craftingType === "workbench" || craftingType === "queen" || craftingType === 'labyrinth-oracle') {
                    craftingType = null;
                }

                this.setState({
                    crafting_type: craftingType,
                    show_hell_forged_section: false,
                    show_purgatory_chains_section: false,
                });
            }
        );
    }

    componentDidUpdate(
        prevProps: Readonly<ActionsProps>,
        prevState: Readonly<ActionsState>
    ): void {
        if (this.props.action_data !== null && this.state.loading) {
            this.setState({
                ...this.state,
                ...this.props.action_data,
                ...{ loading: false },
            });
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

        this.setState(
            { ...this.state, ...actionData, ...{ loading: false } },
            () => {
                updateTimers(this.props.character.id);

                this.props.update_parent_state({
                    monsters: this.state.monsters,
                    raid_monsters: this.state.raid_monsters,
                });
            }
        );
    }

    openCrafting(type: CraftingOptions) {
        this.setState(
            {
                show_purgatory_chains_section: false,
                show_hell_forged_section: false,
            },
            () => {
                this.actionsManager.setCraftingType(type);
            }
        );
    }

    manageExploration() {
        this.setState({
            show_exploration: !this.state.show_exploration,
        });
    }

    manageHellForgedShop() {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_join_pvp: false,
            show_duel_fight: false,
            show_celestial_fight: false,
            show_hell_forged_section: !this.state.show_hell_forged_section,
        });
    }

    managedPurgatoryChainsShop() {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_join_pvp: false,
            show_duel_fight: false,
            show_celestial_fight: false,
            show_purgatory_chains_section:
                !this.state.show_purgatory_chains_section,
        });
    }

    manageDuel() {
        this.setState({
            show_duel_fight: !this.state.show_duel_fight,
        });
    }

    manageJoinPvp() {
        this.setState({
            show_join_pvp: !this.state.show_join_pvp,
        });
    }

    manageFightCelestial() {
        this.setState({
            show_celestial_fight: !this.state.show_celestial_fight,
        });
    }

    manageGamblingSection() {
        this.setState({
            show_gambling_section: !this.state.show_gambling_section,
        });
    }

    isLoading(): boolean {
        return this.state.loading || this.state.monsters.length === 0;
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

    removeCraftingType() {
        this.actionsManager.removeCraftingSection();
    }

    resetDuelData() {
        this.setState({
            duel_fight_info: null,
        });
    }

    render() {
        if (this.isLoading()) {
            return <ComponentLoading />;
        }

        return (
            <div>
                <div className="grid md:grid-cols-4">
                    <div className="md:col-start-1 md:col-span-1">
                        {!this.state.show_exploration &&
                        !this.state.show_duel_fight &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight &&
                        this.props.character !== null ? (
                            <div className="max-w-[100px]">
                                <DropDown
                                    menu_items={this.actionsManager.buildCraftingList(
                                        this.openCrafting.bind(this)
                                    )}
                                    button_title={"Craft/Enchant"}
                                    disabled={this.actionsManager.cannotCraft()}
                                    selected_name={this.actionsManager.getSelectedCraftingOption()}
                                />
                            </div>
                        ) : null}

                        {!this.state.show_duel_fight &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight ? (
                            <div className="mb-4">
                                <SuccessOutlineButton
                                    button_label={"Exploration"}
                                    on_click={this.manageExploration.bind(this)}
                                    additional_css={"w-1/2"}
                                    disabled={this.props.character.is_dead}
                                />
                            </div>
                        ) : null}

                        {this.props.character.can_access_hell_forged ? (
                            <div className="mb-4">
                                <SuccessOutlineButton
                                    button_label={"Hell Forged Gear"}
                                    on_click={this.manageHellForgedShop.bind(
                                        this
                                    )}
                                    additional_css={"w-1/2"}
                                    disabled={this.props.character.is_dead}
                                />
                            </div>
                        ) : null}

                        {this.props.character.can_access_purgatory_chains ? (
                            <div className="mb-4">
                                <SuccessOutlineButton
                                    button_label={"Purgatory Chains Gear"}
                                    on_click={this.managedPurgatoryChainsShop.bind(
                                        this
                                    )}
                                    additional_css={"w-1/2"}
                                    disabled={this.props.character.is_dead}
                                />
                            </div>
                        ) : null}

                        <div className="mb-4">
                            <SuccessOutlineButton
                                button_label={"Slots"}
                                on_click={this.manageGamblingSection.bind(this)}
                                additional_css={"w-1/2"}
                                disabled={this.props.character.is_dead}
                            />
                        </div>

                        {this.props.celestial_id !== 0 &&
                        !this.state.show_exploration &&
                        !this.state.show_duel_fight &&
                        !this.state.show_join_pvp ? (
                            <div className="mb-4">
                                <SuccessOutlineButton
                                    button_label={"Fight Celestial!"}
                                    on_click={this.manageFightCelestial.bind(
                                        this
                                    )}
                                    additional_css={"w-1/2"}
                                    disabled={
                                        this.props.character.is_dead ||
                                        this.props.character
                                            .is_automation_running ||
                                        !this.props.can_engage_celestial
                                    }
                                />
                            </div>
                        ) : null}

                        {this.state.characters_for_dueling.length > 0 &&
                        !this.state.show_exploration &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight ? (
                            <div className="mb-4">
                                <SuccessOutlineButton
                                    button_label={"Duel!"}
                                    on_click={this.manageDuel.bind(this)}
                                    additional_css={"w-1/2"}
                                    disabled={
                                        this.props.character.is_dead ||
                                        this.props.character
                                            .is_automation_running ||
                                        this.props.character.killed_in_pvp
                                    }
                                />
                            </div>
                        ) : null}

                        {this.props.character.can_register_for_pvp &&
                        !this.state.show_duel_fight &&
                        !this.state.show_exploration &&
                        !this.state.show_celestial_fight ? (
                            <div className="mb-4">
                                <SkyOutlineButton
                                    button_label={"Join PVP"}
                                    on_click={this.manageJoinPvp.bind(this)}
                                    additional_css={"w-1/2"}
                                    disabled={this.props.character.is_dead}
                                />
                            </div>
                        ) : null}
                    </div>
                    <div className="md:col-start-2 md:col-span-3 mt-1">
                        {!this.state.show_exploration &&
                        !this.state.show_duel_fight &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight &&
                        this.state.raid_monsters.length === 0 ? (
                            <MonsterActions
                                monsters={this.state.monsters}
                                character={this.props.character}
                                character_statuses={this.props.character_status}
                                is_rank_fights={this.state.show_rank_fight}
                                total_ranks={this.state.total_ranks}
                                is_small={false}
                            >
                                {this.state.crafting_type !== null ? (
                                    <CraftingSection
                                        remove_crafting={this.removeCraftingType.bind(
                                            this
                                        )}
                                        type={this.state.crafting_type}
                                        character_id={this.props.character.id}
                                        user_id={this.props.character.user_id}
                                        cannot_craft={this.actionsManager.cannotCraft()}
                                        fame_tasks={this.props.fame_tasks}
                                        is_small={false}
                                    />
                                ) : null}

                                {this.state.show_hell_forged_section ||
                                this.state.show_purgatory_chains_section ? (
                                    <Shop
                                        type={
                                            this.state.show_hell_forged_section
                                                ? "Hell Forged"
                                                : "Purgatory Chains"
                                        }
                                        character_id={this.props.character.id}
                                        close_hell_forged={this.manageHellForgedShop.bind(
                                            this
                                        )}
                                        close_purgatory_chains={this.managedPurgatoryChainsShop.bind(
                                            this
                                        )}
                                    />
                                ) : null}
                            </MonsterActions>
                        ) : null}

                        {!this.state.show_exploration &&
                        !this.state.show_duel_fight &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight &&
                        this.state.raid_monsters.length > 0 ? (
                            <RaidSection
                                raid_monsters={this.state.raid_monsters}
                                character_id={this.props.character.id}
                                can_attack={this.props.character.can_attack}
                                is_dead={this.props.character.is_dead}
                                is_small={false}
                                character_name={this.props.character.name}
                                user_id={this.props.character.user_id}
                                character_current_health={
                                    this.props.character.health
                                }
                            >
                                {this.state.crafting_type !== null ? (
                                    <CraftingSection
                                        remove_crafting={this.removeCraftingType.bind(
                                            this
                                        )}
                                        type={this.state.crafting_type}
                                        character_id={this.props.character.id}
                                        user_id={this.props.character.user_id}
                                        cannot_craft={this.actionsManager.cannotCraft()}
                                        fame_tasks={this.props.fame_tasks}
                                    />
                                ) : null}

                                {this.state.show_hell_forged_section ||
                                this.state.show_purgatory_chains_section ? (
                                    <Shop
                                        type={
                                            this.state.show_hell_forged_section
                                                ? "Hell Forged"
                                                : "Purgatory Chains"
                                        }
                                        character_id={this.props.character.id}
                                        close_hell_forged={this.manageHellForgedShop.bind(
                                            this
                                        )}
                                        close_purgatory_chains={this.managedPurgatoryChainsShop.bind(
                                            this
                                        )}
                                    />
                                ) : null}
                            </RaidSection>
                        ) : null}

                        {this.state.show_duel_fight ? (
                            <DuelPlayer
                                characters={this.state.characters_for_dueling}
                                duel_data={this.state.duel_fight_info}
                                character={this.props.character}
                                manage_pvp={this.manageDuel.bind(this)}
                                reset_duel_data={this.resetDuelData.bind(this)}
                                is_small={false}
                            />
                        ) : null}

                        {this.state.show_exploration ? (
                            <ExplorationSection
                                character={this.props.character}
                                manage_exploration={this.manageExploration.bind(
                                    this
                                )}
                                monsters={this.state.monsters}
                            />
                        ) : null}

                        {this.state.show_celestial_fight ? (
                            <CelestialFight
                                character={this.props.character}
                                manage_celestial_fight={this.manageFightCelestial.bind(
                                    this
                                )}
                                celestial_id={this.props.celestial_id}
                                update_celestial={this.props.update_celestial}
                            />
                        ) : null}

                        {this.state.show_join_pvp ? (
                            <JoinPvp
                                manage_section={this.manageJoinPvp.bind(this)}
                                character_id={this.props.character.id}
                            />
                        ) : null}

                        {this.state.show_gambling_section ? (
                            <GamblingSection
                                character={this.props.character}
                                close_gambling_section={this.manageGamblingSection.bind(
                                    this
                                )}
                                is_small={false}
                            />
                        ) : null}
                    </div>
                </div>
                <ActionsTimers
                    attack_time_out={this.state.attack_time_out}
                    crafting_time_out={this.state.crafting_time_out}
                    update_attack_timer={this.updateAttackTimer.bind(this)}
                    update_crafting_timer={this.updateCraftingTimer.bind(this)}
                />
            </div>
        );
    }
}
