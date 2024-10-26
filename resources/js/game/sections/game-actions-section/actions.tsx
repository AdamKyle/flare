import React from "react";
import CraftingSection from "../../components/crafting/base-components/crafting-section";
import { CraftingOptions } from "../../components/crafting/base-components/types/crafting-type-options";
import ActionsTimers from "../../components/timers/actions-timers";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import DropDown from "../../components/ui/drop-down/drop-down";
import ComponentLoading from "../../components/ui/loading/component-loading";
import { updateTimers } from "../../lib/ajax/update-timers";
import ActionsManager from "../../lib/game/actions/actions-manager";
import { GameActionState } from "../../lib/game/types/game-state";
import CelestialFight from "./components/celestial-fight";
import ExplorationSection from "./components/exploration-section";
import GamblingSection from "./components/gambling-section";
import RaidSection from "./components/raid-section";
import MonsterActions from "./components/small-actions/monster-actions";
import Shop from "./components/specialty-shops/shop";
import ActionsProps from "./types/actions-props";
import ActionsState from "./types/actions-state";

export default class Actions extends React.Component<
    ActionsProps,
    ActionsState
> {
    private actionsManager: ActionsManager;

    private traverseUpdate: any;

    constructor(props: ActionsProps) {
        super(props);

        this.state = {
            monsters: [],
            raid_monsters: [],
            attack_time_out: 0,
            crafting_time_out: 0,
            crafting_type: null,
            loading: true,
            show_exploration: false,
            show_celestial_fight: false,
            show_hell_forged_section: false,
            show_purgatory_chains_section: false,
            show_twisted_earth_section: false,
            show_gambling_section: false,
        };

        this.actionsManager = new ActionsManager(this);

        // @ts-ignore
        this.traverseUpdate = Echo.private(
            "update-plane-" + this.props.character.user_id,
        );
    }

    componentDidMount() {
        this.setUpState();

        this.props.update_show_map_mobile(true);

        // @ts-ignore
        this.traverseUpdate.listen(
            "Game.Maps.Events.UpdateMap",
            (event: any) => {
                let craftingType = this.state.crafting_type;

                if (
                    craftingType === "workbench" ||
                    craftingType === "queen" ||
                    craftingType === "labyrinth-oracle"
                ) {
                    craftingType = null;
                }

                this.setState({
                    crafting_type: craftingType,
                    show_hell_forged_section: false,
                    show_purgatory_chains_section: false,
                });
            },
        );
    }

    componentDidUpdate(prevProps: ActionsProps): void {
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

        if (this.props.action_data.monsters.length === 0) {
            return;
        }

        if (
            this.props.action_data.monsters[0].id !== this.state.monsters[0].id
        ) {
            if (this.props.action_data.monsters.length > 0) {
                this.setState({
                    monsters: this.props.action_data.monsters,
                });
            }
        }

        if (this.props.action_data.raid_monsters !== this.state.raid_monsters) {
            this.setState({
                raid_monsters: this.props.action_data.raid_monsters,
            });
        }

        if (typeof this.props.character_position === "undefined") {
            return;
        }

        if (typeof prevProps.character_position === "undefined") {
            return;
        }

        if (
            this.props.character_position !== null &&
            prevProps.character_position !== null
        ) {
            if (
                (this.props.character_position.x !==
                    prevProps.character_position.x &&
                    this.props.character_position.y !==
                        prevProps.character_position.y) ||
                this.props.character_position.game_map_id !==
                    prevProps.character_position.game_map_id
            ) {
                this.setState({
                    show_celestial_fight: false,
                });
            }
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
            },
        );
    }

    openCrafting(type: CraftingOptions) {
        this.setState(
            {
                show_purgatory_chains_section: false,
                show_hell_forged_section: false,
                show_twisted_earth_section: false,
            },
            () => {
                this.actionsManager.setCraftingType(type);
            },
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
            show_celestial_fight: false,
            show_twisted_earth_section: false,
            show_hell_forged_section: !this.state.show_hell_forged_section,
        });
    }

    managedPurgatoryChainsShop() {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_celestial_fight: false,
            show_twisted_earth_section: false,
            show_purgatory_chains_section:
                !this.state.show_purgatory_chains_section,
        });
    }

    managedTwistedEarthShop() {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_celestial_fight: false,
            show_purgatory_chains_section: false,
            show_twisted_earth_section: !this.state.show_twisted_earth_section,
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

    removeCraftingType() {
        this.actionsManager.removeCraftingSection();
    }

    getTypeOfSpecialtyGear() {
        if (this.state.show_hell_forged_section) {
            return "Hell Forged";
        }

        if (this.state.show_purgatory_chains_section) {
            return "Purgatory Chains";
        }

        if (this.state.show_twisted_earth_section) {
            return "Twisted Earth";
        }

        return "";
    }

    render() {
        if (this.isLoading()) {
            return <ComponentLoading />;
        }

        return (
            <div className="p-4">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div className="md:col-span-1 space-y-4">
                        {!this.state.show_exploration &&
                            !this.state.show_celestial_fight &&
                            this.props.character !== null && (
                                <div className="w-full">
                                    <DropDown
                                        menu_items={this.actionsManager.buildCraftingList(
                                            this.openCrafting.bind(this),
                                        )}
                                        button_title={"Craft/Enchant"}
                                        disabled={this.actionsManager.cannotCraft()}
                                        selected_name={this.actionsManager.getSelectedCraftingOption()}
                                    />
                                </div>
                            )}

                        {!this.state.show_celestial_fight && (
                            <div className="w-full">
                                <SuccessOutlineButton
                                    button_label={"Exploration"}
                                    on_click={this.manageExploration.bind(this)}
                                    additional_css={"w-full"}
                                    disabled={this.props.character.is_dead}
                                />
                            </div>
                        )}

                        {this.props.character.can_access_hell_forged && (
                            <div className="w-full">
                                <SuccessOutlineButton
                                    button_label={"Hell Forged Gear"}
                                    on_click={this.manageHellForgedShop.bind(
                                        this,
                                    )}
                                    additional_css={"w-full"}
                                    disabled={this.props.character.is_dead}
                                />
                            </div>
                        )}

                        {this.props.character.can_access_purgatory_chains && (
                            <div className="w-full">
                                <SuccessOutlineButton
                                    button_label={"Purgatory Chains Gear"}
                                    on_click={this.managedPurgatoryChainsShop.bind(
                                        this,
                                    )}
                                    additional_css={"w-full"}
                                    disabled={this.props.character.is_dead}
                                />
                            </div>
                        )}

                        {this.props.character.can_access_twisted_memories && (
                            <div className="w-full">
                                <SuccessOutlineButton
                                    button_label={"Twisted Earth Gear"}
                                    on_click={this.managedTwistedEarthShop.bind(
                                        this,
                                    )}
                                    additional_css={"w-full"}
                                    disabled={this.props.character.is_dead}
                                />
                            </div>
                        )}

                        <div className="w-full">
                            <SuccessOutlineButton
                                button_label={"Slots"}
                                on_click={this.manageGamblingSection.bind(this)}
                                additional_css={"w-full"}
                                disabled={this.props.character.is_dead}
                            />
                        </div>

                        {this.props.celestial_id !== 0 &&
                            !this.state.show_exploration &&
                            this.props.can_engage_celestial && (
                                <div className="w-full">
                                    <SuccessOutlineButton
                                        button_label={"Fight Celestial!"}
                                        on_click={this.manageFightCelestial.bind(
                                            this,
                                        )}
                                        additional_css={"w-full"}
                                        disabled={
                                            this.props.character.is_dead ||
                                            this.props.character
                                                .is_automation_running ||
                                            !this.props.can_engage_celestial
                                        }
                                    />
                                </div>
                            )}
                    </div>
                    <div className="md:col-span-3 mt-4">
                        {!this.state.show_exploration &&
                            !this.state.show_celestial_fight &&
                            this.state.raid_monsters.length === 0 && (
                                <MonsterActions
                                    monsters={this.state.monsters}
                                    character={this.props.character}
                                    character_statuses={
                                        this.props.character_status
                                    }
                                    is_small={false}
                                >
                                    {this.state.crafting_type !== null && (
                                        <CraftingSection
                                            remove_crafting={this.removeCraftingType.bind(
                                                this,
                                            )}
                                            type={this.state.crafting_type}
                                            character_id={
                                                this.props.character.id
                                            }
                                            user_id={
                                                this.props.character.user_id
                                            }
                                            cannot_craft={this.actionsManager.cannotCraft()}
                                            fame_tasks={this.props.fame_tasks}
                                            is_small={false}
                                        />
                                    )}

                                    {(this.state.show_hell_forged_section ||
                                        this.state
                                            .show_purgatory_chains_section ||
                                        this.state
                                            .show_twisted_earth_section) && (
                                        <Shop
                                            type={this.getTypeOfSpecialtyGear()}
                                            character_id={
                                                this.props.character.id
                                            }
                                            close_hell_forged={this.manageHellForgedShop.bind(
                                                this,
                                            )}
                                            close_purgatory_chains={this.managedPurgatoryChainsShop.bind(
                                                this,
                                            )}
                                            close_twisted_earth={this.managedTwistedEarthShop.bind(
                                                this,
                                            )}
                                        />
                                    )}
                                </MonsterActions>
                            )}

                        {!this.state.show_exploration &&
                            !this.state.show_celestial_fight &&
                            this.state.raid_monsters.length > 0 && (
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
                                    {this.state.crafting_type !== null && (
                                        <CraftingSection
                                            remove_crafting={this.removeCraftingType.bind(
                                                this,
                                            )}
                                            type={this.state.crafting_type}
                                            character_id={
                                                this.props.character.id
                                            }
                                            user_id={
                                                this.props.character.user_id
                                            }
                                            cannot_craft={this.actionsManager.cannotCraft()}
                                            fame_tasks={this.props.fame_tasks}
                                        />
                                    )}

                                    {(this.state.show_hell_forged_section ||
                                        this.state
                                            .show_purgatory_chains_section) && (
                                        <Shop
                                            type={
                                                this.state
                                                    .show_hell_forged_section
                                                    ? "Hell Forged"
                                                    : "Purgatory Chains"
                                            }
                                            character_id={
                                                this.props.character.id
                                            }
                                            close_hell_forged={this.manageHellForgedShop.bind(
                                                this,
                                            )}
                                            close_purgatory_chains={this.managedPurgatoryChainsShop.bind(
                                                this,
                                            )}
                                        />
                                    )}
                                </RaidSection>
                            )}

                        {this.state.show_exploration && (
                            <ExplorationSection
                                character={this.props.character}
                                manage_exploration={this.manageExploration.bind(
                                    this,
                                )}
                                monsters={this.state.monsters}
                            />
                        )}

                        {this.state.show_celestial_fight && (
                            <CelestialFight
                                character={this.props.character}
                                manage_celestial_fight={this.manageFightCelestial.bind(
                                    this,
                                )}
                                celestial_id={this.props.celestial_id}
                                update_celestial={this.props.update_celestial}
                            />
                        )}

                        {this.state.show_gambling_section && (
                            <GamblingSection
                                character={this.props.character}
                                close_gambling_section={this.manageGamblingSection.bind(
                                    this,
                                )}
                                is_small={false}
                            />
                        )}
                    </div>
                </div>
                <ActionsTimers user_id={this.props.character.user_id} />
            </div>
        );
    }
}
