import React from "react";
import CraftingSection from "../../components/crafting/base-components/crafting-section";
import { CraftingOptions } from "../../components/crafting/base-components/types/crafting-type-options";
import ActionsTimers from "../../components/timers/actions-timers";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import DangerOutlineButton from "../../components/ui/buttons/danger-outline-button";
import DropDown from "../../components/ui/drop-down/drop-down";
import ComponentLoading from "../../components/ui/loading/component-loading";
import { updateTimers } from "../../lib/ajax/update-timers";
import Ajax from "../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ActionsManager from "../../lib/game/actions/actions-manager";
import { GameActionState } from "../../lib/game/types/game-state";
import CelestialFight from "./components/celestial-fight";
import ExplorationOutputSection from "./components/exploration-output-section";
import ExplorationSection from "./components/exploration-section";
import GamblingSection from "./components/gambling-section";
import RaidSection from "./components/raid-section";
import MonsterActions from "./components/small-actions/monster-actions";
import Shop from "./components/specialty-shops/shop";
import ActionsProps from "./types/actions-props";
import ActionsState from "./types/actions-state";
import WarningAlert from "../../components/ui/alerts/simple-alerts/warning-alert";

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
            celestial_time_out: 0,
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
                crafting_type: null,
                show_exploration: false,
                show_hell_forged_section: false,
                show_purgatory_chains_section: false,
                show_twisted_earth_section: false,
            },
            () => {
                this.actionsManager.setCraftingType(type);
            },
        );
    }

    manageExploration() {
        const showExploration = !this.state.show_exploration;

        this.setState({
            show_exploration: showExploration,
            show_celestial_fight: false,
            crafting_type: null,
            show_hell_forged_section: false,
            show_purgatory_chains_section: false,
            show_twisted_earth_section: false,
        });
    }

    manageHellForgedShop() {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_twisted_earth_section: false,
            show_purgatory_chains_section: false,
            show_hell_forged_section: !this.state.show_hell_forged_section,
        });
    }

    managedPurgatoryChainsShop() {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_twisted_earth_section: false,
            show_hell_forged_section: false,
            show_purgatory_chains_section:
                !this.state.show_purgatory_chains_section,
        });
    }

    managedTwistedEarthShop() {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_purgatory_chains_section: false,
            show_hell_forged_section: false,
            show_twisted_earth_section: !this.state.show_twisted_earth_section,
        });
    }

    manageFightCelestial() {
        const showCelestialFight = !this.state.show_celestial_fight;

        this.setState({
            show_exploration: false,
            show_celestial_fight: showCelestialFight,
            show_hell_forged_section: false,
            show_purgatory_chains_section: false,
            show_twisted_earth_section: false,
        });
    }

    manageGamblingSection() {
        const showGamblingSection = !this.state.show_gambling_section;

        this.setState({
            show_gambling_section: showGamblingSection,
            show_exploration: false,
            show_celestial_fight: false,
        });
    }

    isLoading(): boolean {
        return this.state.loading || this.state.monsters.length === 0;
    }

    removeCraftingType() {
        this.actionsManager.removeCraftingSection();
    }

    isFactionLoyaltyAutomationRunning(): boolean {
        return this.props.character.is_faction_loyalty_automation_running;
    }

    isDelveRunning(): boolean {
        return this.props.character.is_delve_running;
    }

    isAnyAutomationRunning(): boolean {
        return (
            this.props.character.is_automation_running ||
            this.isFactionLoyaltyAutomationRunning() ||
            this.isDelveRunning()
        );
    }

    canOpenCraftingMenu(): boolean {
        return (
            this.state.crafting_time_out > 0 ||
            this.props.character_status.is_dead
        );
    }

    selectedCraftingTypeIsBlockedByAutomation(): boolean {
        return (
            this.isFactionLoyaltyAutomationRunning() &&
            this.state.crafting_type === "craft"
        );
    }

    canShowCelestialFight(): boolean {
        return (
            this.props.celestial_id !== 0 &&
            this.props.celestial_id !== null &&
            this.props.character.can_engage_celestials
        );
    }

    automationName(): string {
        return this.props.character.active_automation?.name ?? "Automation";
    }

    automationRestrictionMessage(): string {
        if (this.isFactionLoyaltyAutomationRunning()) {
            return "Faction Loyalty Automation is running. You cannot Delve, Explore, manually Fight, or craft items while it is running.";
        }

        if (this.isDelveRunning()) {
            return "Delve is running. You cannot Explore, manually Fight, or use Faction Loyalty while it is running.";
        }

        return "Exploration is running. You cannot Delve, use Faction Loyalty, or manually Fight while it is running.";
    }

    automationStopRoute(): string {
        if (this.isFactionLoyaltyAutomationRunning()) {
            return (
                "faction-loyalty-automation/" +
                this.props.character.id +
                "/stop"
            );
        }

        if (this.isDelveRunning()) {
            return "delve/" + this.props.character.id + "/stop";
        }

        return "automation/" + this.props.character.id + "/stop";
    }

    stopRunningAutomation() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax().setRoute(this.automationStopRoute()).doAjaxCall(
                    "post",
                    (result: AxiosResponse) => {
                        this.setState(
                            {
                                loading: false,
                                crafting_type: null,
                                show_exploration: false,
                            },
                            () => {
                                updateTimers(this.props.character.id);
                            },
                        );
                    },
                    (error: AxiosError) => {
                        this.setState({
                            loading: false,
                        });
                    },
                );
            },
        );
    }

    renderAutomationBlockedNotice() {
        return (
            <div aria-live="polite">
                <WarningAlert additional_css={"mt-4"}>
                    <p className="my-2">
                        {this.automationRestrictionMessage()}
                    </p>
                    {this.isFactionLoyaltyAutomationRunning() ? (
                        <p className="my-2">
                            Enchanting, alchemy, trinketry, gem crafting, and
                            other crafting-menu actions are still allowed.
                        </p>
                    ) : null}
                    <p className="my-2">Would you like to stop it?</p>
                    <DangerOutlineButton
                        button_label={"Stop " + this.automationName()}
                        on_click={this.stopRunningAutomation.bind(this)}
                        disabled={this.state.loading}
                        additional_css={""}
                    />
                </WarningAlert>
            </div>
        );
    }

    getTypeOfSpecialtyGear() {
        if (this.state.show_hell_forged_section) {
            return "Hell Forged";
        }

        if (this.state.show_purgatory_chains_section) {
            return "Purgatory Chains";
        }

        return "Twisted Earth";
    }

    createMonster() {
        if (this.isAnyAutomationRunning()) {
            return this.renderAutomationBlockedNotice();
        }

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
                character_statuses={this.props.character_status}
                is_small={false}
            >
                {this.renderActionSlot()}
            </MonsterActions>
        );
    }

    closeMonsterSection() {
        this.setState({
            show_exploration: false,
            show_celestial_fight: false,
        });
    }

    reviveCharacter() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "character/" + this.props.character.id + "/revive",
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({
                                loading: false,
                            });
                        },
                    );
            },
        );
    }

    renderActionButtons() {
        return (
            <div className="grid gap-2">
                <DropDown
                    menu_items={this.actionsManager.buildCraftingList(
                        this.openCrafting.bind(this),
                    )}
                    button_title={"Craft/Enchant"}
                    disabled={this.canOpenCraftingMenu()}
                />
                <SuccessOutlineButton
                    button_label={
                        this.props.character.is_at_delve_location
                            ? "Delve"
                            : "Exploration"
                    }
                    on_click={this.manageExploration.bind(this)}
                    disabled={
                        this.isFactionLoyaltyAutomationRunning() ||
                        this.isDelveRunning()
                    }
                    additional_css={"w-full"}
                />
                <SuccessOutlineButton
                    button_label={"Slots"}
                    on_click={this.manageGamblingSection.bind(this)}
                    disabled={this.props.character_status.is_dead}
                    additional_css={"w-full"}
                />
                {this.canShowCelestialFight() ? (
                    <SuccessOutlineButton
                        button_label={"Celestial Fight"}
                        on_click={this.manageFightCelestial.bind(this)}
                        disabled={
                            this.state.celestial_time_out > 0 ||
                            this.isAnyAutomationRunning()
                        }
                        additional_css={"w-full"}
                    />
                ) : null}
                {this.props.can_access_hell_forged_shop ? (
                    <SuccessOutlineButton
                        button_label={"Hell Forged Gear"}
                        on_click={this.manageHellForgedShop.bind(this)}
                        disabled={this.props.character_status.is_dead}
                        additional_css={"w-full"}
                    />
                ) : null}
                {this.props.can_access_purgatory_chains_shop ? (
                    <SuccessOutlineButton
                        button_label={"Purgatory Chains Gear"}
                        on_click={this.managedPurgatoryChainsShop.bind(this)}
                        disabled={this.props.character_status.is_dead}
                        additional_css={"w-full"}
                    />
                ) : null}
                {this.props.can_access_twisted_earth_shop ? (
                    <SuccessOutlineButton
                        button_label={"Twisted Earth"}
                        on_click={this.managedTwistedEarthShop.bind(this)}
                        disabled={this.props.character_status.is_dead}
                        additional_css={"w-full"}
                    />
                ) : null}
            </div>
        );
    }

    renderCraftingSection() {
        if (
            this.state.crafting_type !== null &&
            this.selectedCraftingTypeIsBlockedByAutomation()
        ) {
            return this.renderAutomationBlockedNotice();
        }

        if (this.state.crafting_type !== null) {
            return (
                <CraftingSection
                    remove_crafting={this.removeCraftingType.bind(this)}
                    type={this.state.crafting_type}
                    character_id={this.props.character.id}
                    user_id={this.props.character.user_id}
                    crafting_time_out={this.state.crafting_time_out}
                    cannot_craft={this.actionsManager.cannotCraft()}
                    update_crafting_time_out={this.actionsManager.updateCraftingTimer.bind(
                        this.actionsManager,
                    )}
                    fame_tasks={this.props.fame_tasks}
                />
            );
        }

        return null;
    }

    renderActionContent() {
        const celestialFight = this.renderCelestialFight();
        const actionSlot = this.renderActionSlot();
        let fightContent = null;

        if (this.state.show_exploration) {
            fightContent = this.renderExploration();
        } else if (
            this.isAnyAutomationRunning() &&
            (actionSlot !== null || this.state.show_gambling_section)
        ) {
            fightContent = actionSlot;
        } else if (celestialFight === null) {
            fightContent = this.createMonster();
        } else {
            fightContent = (
                <div className="grid gap-4">
                    {actionSlot}
                    {celestialFight}
                </div>
            );
        }

        return (
            <div className="grid gap-4">
                {fightContent}
                {this.state.show_gambling_section ? (
                    <div className="mx-auto w-full md:w-2/3">
                        <GamblingSection
                            character={this.props.character}
                            close_gambling_section={this.manageGamblingSection.bind(
                                this,
                            )}
                            is_small={false}
                        />
                    </div>
                ) : null}
                {!this.state.show_exploration ? (
                    <ExplorationOutputSection
                        character_id={this.props.character.id}
                        exploration_output={this.props.exploration_output}
                    />
                ) : null}
            </div>
        );
    }

    renderExploration() {
        if (this.isFactionLoyaltyAutomationRunning() || this.isDelveRunning()) {
            return (
                <div className="mx-auto w-full md:w-2/3">
                    {this.renderAutomationBlockedNotice()}
                </div>
            );
        }

        return (
            <ExplorationSection
                manage_exploration={this.manageExploration.bind(this)}
                character={this.props.character}
                monsters={this.state.monsters}
                exploration_output={this.props.exploration_output}
            />
        );
    }

    renderSpecialtyShop() {
        if (
            !this.state.show_hell_forged_section &&
            !this.state.show_purgatory_chains_section &&
            !this.state.show_twisted_earth_section
        ) {
            return null;
        }

        return (
            <Shop
                type={this.getTypeOfSpecialtyGear()}
                close_hell_forged={this.manageHellForgedShop.bind(this)}
                close_purgatory_chains={this.managedPurgatoryChainsShop.bind(
                    this,
                )}
                close_twisted_earth={this.managedTwistedEarthShop.bind(this)}
                character_id={this.props.character.id}
                is_dead={this.props.character.is_dead}
                cannot_craft={this.actionsManager.cannotCraft()}
            />
        );
    }

    renderActionSlot() {
        const specialtyShop = this.renderSpecialtyShop();

        if (specialtyShop !== null) {
            return (
                <div className="mx-auto w-full md:w-2/3">{specialtyShop}</div>
            );
        }

        const craftingSection = this.renderCraftingSection();

        if (craftingSection === null) {
            return null;
        }

        return <div className="mx-auto w-full md:w-2/3">{craftingSection}</div>;
    }

    renderCelestialFight() {
        if (!this.state.show_celestial_fight || !this.canShowCelestialFight()) {
            return null;
        }

        return (
            <CelestialFight
                character={this.props.character}
                manage_celestial_fight={this.manageFightCelestial.bind(this)}
                celestial_id={this.props.celestial_id}
                update_celestial={this.props.update_celestial}
            />
        );
    }

    render() {
        if (this.isLoading()) {
            return <ComponentLoading />;
        }

        return (
            <div>
                <div className="grid lg:grid-cols-[220px_minmax(0,1fr)] gap-4 items-start">
                    <div>{this.renderActionButtons()}</div>
                    <div>{this.renderActionContent()}</div>
                </div>

                <div className="mt-4 mb-4">
                    <ActionsTimers user_id={this.props.character.user_id} />
                </div>
            </div>
        );
    }
}
