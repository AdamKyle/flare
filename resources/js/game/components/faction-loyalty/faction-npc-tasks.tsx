import React, { ReactNode } from "react";
import { AxiosError, AxiosResponse } from "axios";
import FactionNpcSectionProps from "./types/faction-npc-section-props";
import OrangeProgressBar from "../../components/ui/progress-bars/orange-progress-bar";
import {
    FameTasks,
    FactionLoyaltyWarningNotice,
} from "./deffinitions/faction-loaylaty";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
import DangerOutlineButton from "../../components/ui/buttons/danger-outline-button";
import BountyFightAjax from "./ajax/bounty-fight-ajax";
import { serviceContainer } from "../../lib/containers/core-container";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../ui/alerts/simple-alerts/success-alert";
import Revive from "../../sections/game-actions-section/components/fight-section/revive";
import WarningAlert from "../ui/alerts/simple-alerts/warning-alert";
import DangerAlert from "../ui/alerts/simple-alerts/danger-alert";
import HandleCraftingAjax from "./ajax/handle-crafting-ajax";
import { ItemType } from "../items/enums/item-type";
import InfoAlert from "../ui/alerts/simple-alerts/info-alert";
import DropDown from "../ui/drop-down/drop-down";
import { startCase, toLower } from "lodash";
import TimerProgressBar from "../ui/progress-bars/timer-progress-bar";
import Ajax from "../../lib/ajax/ajax";

export default class FactionNpcTasks extends React.Component<
    FactionNpcSectionProps,
    any
> {
    private readonly fightAjax: BountyFightAjax;

    private readonly craftingAjax: HandleCraftingAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            attacking: false,
            crafting: false,
            success_message: null,
            error_message: null,
            must_revive: false,
            attack_type_selected: this.props.attack_type ?? "attack",
            warning_notice:
                this.props.faction_loyalty_npc.faction_loyalty_warning_notice ??
                null,
            dismissing_warning_notice: false,
        };

        this.fightAjax = serviceContainer().fetch(BountyFightAjax);

        this.craftingAjax = serviceContainer().fetch(HandleCraftingAjax);
    }

    componentDidUpdate(previousProps: FactionNpcSectionProps): void {
        if (previousProps.attack_type !== this.props.attack_type) {
            this.setState({
                attack_type_selected: this.props.attack_type ?? "attack",
            });
        }

        if (
            previousProps.faction_loyalty_npc.faction_loyalty_warning_notice !==
            this.props.faction_loyalty_npc.faction_loyalty_warning_notice
        ) {
            this.setState({
                warning_notice:
                    this.props.faction_loyalty_npc
                        .faction_loyalty_warning_notice ?? null,
            });
        }
    }

    bountyTask(monsterId?: number) {
        if (this.isAnyAutomationRunning()) {
            return;
        }

        if (!monsterId) {
            return;
        }

        this.setState(
            {
                attacking: true,
                success_message: null,
                error_message: null,
            },
            () => {
                if (!monsterId) {
                    return;
                }

                this.fightAjax.doAjaxCall(
                    this,
                    {
                        monster_id: monsterId,
                        npc_id: this.props.faction_loyalty_npc.npc_id,
                        attack_type: this.state.attack_type_selected,
                    },
                    this.props.character_id,
                );
            },
        );
    }

    craftingTask(itemType: string, itemId?: number) {
        if (this.isAnyAutomationRunning()) {
            return;
        }

        if (!itemId) {
            return;
        }

        const armourTypes = [
            ItemType.BODY,
            ItemType.SHIELD,
            ItemType.LEGGINGS,
            ItemType.BOOTS,
            ItemType.SLEEVES,
            ItemType.GLOVES,
            ItemType.HELMET,
        ];

        const spellTypes = [ItemType.SPELL_DAMAGE, ItemType.SPELL_HEALING];

        let typeToCraft = itemType;

        if (armourTypes.includes(itemType as ItemType)) {
            typeToCraft = "armour";
        }

        if (spellTypes.includes(itemType as ItemType)) {
            typeToCraft = "spell";
        }

        this.setState(
            {
                crafting: true,
                success_message: null,
                error_message: null,
            },
            () => {
                this.craftingAjax.doAjaxCall(
                    this,
                    {
                        item_to_craft: itemId,
                        type: typeToCraft,
                        craft_for_event: false,
                        craft_for_npc: true,
                    },
                    this.props.character_id,
                );
            },
        );
    }

    showCheckMark(fameTask: FameTasks): ReactNode {
        if (fameTask.current_amount === fameTask.required_amount) {
            return (
                <i className="fas fa-check text-green-700 dark:text-green-500"></i>
            );
        }

        return;
    }

    isBountyActionDisabled() {
        return (
            !this.props.can_attack ||
            this.state.attacking ||
            this.state.must_revive ||
            this.isAnyAutomationRunning() ||
            !(
                this.props.faction_loyalty_npc.npc.game_map_id ===
                this.props.character_map_id
            ) ||
            !this.props.faction_loyalty_npc.currently_helping
        );
    }

    isCraftingTaskButtonDisabled(fameTask: FameTasks) {
        return (
            !this.props.can_craft ||
            this.state.crafting ||
            this.state.must_revive ||
            this.isAnyAutomationRunning() ||
            fameTask.current_amount === fameTask.required_amount ||
            !this.props.faction_loyalty_npc.currently_helping ||
            !(
                this.props.faction_loyalty_npc.npc.game_map_id ===
                this.props.character_map_id
            )
        );
    }

    renderTasks(fameTasks: FameTasks[], bounties: boolean) {
        return fameTasks
            .filter((fameTask: FameTasks) => {
                return bounties
                    ? fameTask.type === "bounty"
                    : fameTask.type !== "bounty";
            })
            .map((fameTask: FameTasks) => {
                return (
                    <>
                        <dt>
                            {bounties
                                ? fameTask.monster_name
                                : fameTask.item_name +
                                  " [" +
                                  fameTask.type +
                                  "]"}
                        </dt>
                        <dd className="flex flex-justify">
                            <div className="flex-1 mr-2">
                                {this.showCheckMark(fameTask)}{" "}
                                {fameTask.current_amount} /{" "}
                                {fameTask.required_amount}
                            </div>
                            <div className="flex-1 ml-2">
                                {bounties ? (
                                    <PrimaryOutlineButton
                                        button_label={"Attack"}
                                        on_click={() =>
                                            this.bountyTask(fameTask.monster_id)
                                        }
                                        disabled={
                                            this.isBountyActionDisabled() ||
                                            fameTask.current_amount ===
                                                fameTask.required_amount
                                        }
                                    />
                                ) : (
                                    <SuccessOutlineButton
                                        button_label={"Craft"}
                                        on_click={() => {
                                            this.craftingTask(
                                                fameTask.type,
                                                fameTask.item_id,
                                            );
                                        }}
                                        disabled={this.isCraftingTaskButtonDisabled(
                                            fameTask,
                                        )}
                                    />
                                )}
                            </div>
                        </dd>
                    </>
                );
            });
    }

    updateMustRevive() {
        this.setState({
            must_revive: false,
        });
    }

    setAttackType(attackType: string) {
        if (this.isAnyAutomationRunning()) {
            return;
        }

        this.setState({
            attack_type_selected: attackType,
        });

        if (typeof this.props.set_attack_type !== "undefined") {
            this.props.set_attack_type(attackType);
        }
    }

    createTypeFilterDropDown() {
        return [
            {
                name: "Attack",
                icon_class: "ra ra-sword",
                on_click: () => this.setAttackType("attack"),
            },
            {
                name: "Cast",
                icon_class: "ra ra-burning-book",
                on_click: () => this.setAttackType("cast"),
            },
            {
                name: "Attack and Cast",
                icon_class: "ra ra-lightning-sword",
                on_click: () => this.setAttackType("attack_and_cast"),
            },
            {
                name: "Cast and Attack",
                icon_class: "ra ra-lightning-sword",
                on_click: () => this.setAttackType("cast_and_attack"),
            },
            {
                name: "Defend",
                icon_class: "ra ra-round-shield",
                on_click: () => this.setAttackType("defend"),
            },
        ];
    }

    buildDropDownTitle(): string {
        const attackType = startCase(toLower(this.state.attack_type_selected));

        return "Current Attack Type: " + attackType;
    }

    isAnyAutomationRunning(): boolean {
        return (
            this.props.is_automation_running === true ||
            this.props.is_faction_loyalty_automation_running === true ||
            this.props.is_delve_running === true
        );
    }

    showAutomationScreen() {
        if (this.props.automation_disabled_reason != null) {
            return;
        }

        if (typeof this.props.show_automation_screen !== "undefined") {
            this.props.show_automation_screen();
        }
    }

    stopAutomation() {
        if (typeof this.props.stop_automation !== "undefined") {
            this.props.stop_automation();
        }
    }

    dismissWarningNotice() {
        this.setState(
            {
                dismissing_warning_notice: true,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "faction-loyalty-automation/" +
                            this.props.character_id +
                            "/warning-notice/read",
                    )
                    .doAjaxCall(
                        "post",
                        (_result: AxiosResponse) => {
                            this.setState({
                                warning_notice: null,
                                dismissing_warning_notice: false,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({
                                dismissing_warning_notice: false,
                            });

                            if (error.response) {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    }

    renderWarningNotice(): ReactNode {
        const warningNotice: FactionLoyaltyWarningNotice | null =
            this.state.warning_notice;

        if (warningNotice === null) {
            return null;
        }

        return (
            <WarningAlert additional_css={"my-2"}>
                <div className="flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
                    <span>{warningNotice.message}</span>
                    <DangerOutlineButton
                        button_label={"Dismiss"}
                        on_click={this.dismissWarningNotice.bind(this)}
                        disabled={this.state.dismissing_warning_notice}
                        additional_css={"w-full sm:w-auto"}
                    />
                </div>
            </WarningAlert>
        );
    }

    renderAutomationAction(): ReactNode {
        if (this.props.is_faction_loyalty_automation_running) {
            return (
                <div className="mt-3 w-full" aria-live="polite">
                    {this.props.automation_time_out &&
                    this.props.automation_time_out > 0 ? (
                        <TimerProgressBar
                            time_remaining={this.props.automation_time_out}
                            time_out_label={"Faction Loyalty Automation"}
                            update_time_remaining={
                                this.props.update_automation_timer
                            }
                        />
                    ) : (
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                            Faction Loyalty Automation is running. Timer details
                            are refreshing.
                        </p>
                    )}
                    <div className="mt-4 text-center">
                        <DangerOutlineButton
                            button_label={"Stop Automation"}
                            on_click={this.stopAutomation.bind(this)}
                            disabled={
                                this.props.is_automation_processing === true
                            }
                            additional_css={"w-full sm:w-auto"}
                        />
                    </div>
                </div>
            );
        }

        return (
            <div className="mt-3 w-full" aria-live="polite">
                <button
                    type="button"
                    className="w-full py-2 px-3 text-xs border-blue-500 border-2 font-medium text-center text-gray-900 dark:text-gray-200 hover:text-gray-200 dark:hover:text-gray-300 hover:bg-blue-600 rounded-sm focus:ring-4 focus:ring-blue-300 dark:hover:bg-blue-600 dark:focus:ring-blue-800 disabled:bg-blue-600 disabled:bg-opacity-75 dark:disabled:bg-opacity-50 dark:disabled:bg-blue-500 disabled:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 dark:focus-visible:ring-white focus-visible:ring-opacity-75"
                    onClick={this.showAutomationScreen.bind(this)}
                    disabled={
                        this.props.automation_disabled_reason != null ||
                        this.props.is_automation_processing === true
                    }
                    aria-describedby={
                        this.props.automation_disabled_reason != null
                            ? "faction-loyalty-automation-disabled-reason"
                            : undefined
                    }
                    aria-busy={this.props.is_automation_processing}
                >
                    Automate Faction Loyalty
                </button>
                {this.props.automation_disabled_reason != null ? (
                    <p
                        id="faction-loyalty-automation-disabled-reason"
                        className="mt-2 text-sm text-red-700 dark:text-red-300"
                    >
                        {this.props.automation_disabled_reason}
                    </p>
                ) : null}
            </div>
        );
    }

    renderTaskSection(): ReactNode {
        return (
            <div>
                <div>
                    <h3 className="my-2"> Bounties </h3>
                    {this.renderWarningNotice()}
                    {this.props.character_map_id !==
                    this.props.faction_loyalty_npc.npc.game_map_id ? (
                        <WarningAlert additional_css={"my-2"}>
                            You are not on the same plane as this NPC. You
                            cannot complete bounty tasks or start Faction
                            Loyalty Automation until you are on this NPC&apos;s
                            plane.
                        </WarningAlert>
                    ) : null}
                    <InfoAlert additional_css="my-2">
                        <p>
                            Bounties must be completed on the NPC&apos;s plane.
                            Faction Loyalty Automation can handle bounty and
                            crafting tasks while it is running.
                        </p>
                    </InfoAlert>
                    <DropDown
                        menu_items={this.createTypeFilterDropDown()}
                        button_title={this.buildDropDownTitle()}
                        disabled={this.isAnyAutomationRunning()}
                    />
                    <dl>
                        {this.renderTasks(
                            this.props.faction_loyalty_npc
                                .faction_loyalty_npc_tasks.fame_tasks,
                            true,
                        )}
                    </dl>
                    {this.state.attacking ? <LoadingProgressBar /> : null}
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <div>
                    <h3 className="my-2"> Crafting </h3>
                    <dl>
                        {this.renderTasks(
                            this.props.faction_loyalty_npc
                                .faction_loyalty_npc_tasks.fame_tasks,
                            false,
                        )}
                    </dl>
                    {this.state.crafting ? <LoadingProgressBar /> : null}
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                    {this.renderAutomationAction()}
                </div>
            </div>
        );
    }

    renderFactionNpcTasks(): ReactNode {
        return (
            <div>
                <div>
                    <OrangeProgressBar
                        primary_label={
                            this.props.faction_loyalty_npc.npc.real_name +
                            " Fame LV: " +
                            this.props.faction_loyalty_npc.current_level +
                            "/" +
                            this.props.faction_loyalty_npc.max_level
                        }
                        secondary_label={
                            this.props.faction_loyalty_npc.current_fame +
                            "/" +
                            this.props.faction_loyalty_npc.next_level_fame +
                            " Fame"
                        }
                        percentage_filled={
                            (this.props.faction_loyalty_npc.current_fame /
                                this.props.faction_loyalty_npc
                                    .next_level_fame) *
                            100
                        }
                        push_down={false}
                    />
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                {this.state.success_message !== null ? (
                    <SuccessAlert additional_css={"my-2"}>
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}

                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-2"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}

                {this.props.faction_loyalty_npc.faction_loyalty_npc_tasks
                    .fame_tasks.length > 0 ? (
                    <div>{this.renderTaskSection()}</div>
                ) : (
                    <SuccessAlert additional_css={"my-2"}>
                        You have completed all this NPC's tasks. By being
                        aligned to this Faction, your kingdoms for the plane the
                        NPC lives on, will receive a Item Defence bonus based on
                        the level of the NPC and the amount of NPC's you have
                        helped! This bonus is automatically applied to all
                        present and future kingdoms.
                    </SuccessAlert>
                )}
            </div>
        );
    }

    render() {
        if (!this.state.must_revive) {
            return this.renderFactionNpcTasks();
        }

        return (
            <div>
                <WarningAlert additional_css={"my-4"}>
                    {this.state.success_message}
                </WarningAlert>
                <Revive
                    can_attack={this.props.can_attack}
                    is_character_dead={true}
                    character_id={this.props.character_id}
                    revive_call_back={this.updateMustRevive.bind(this)}
                />
            </div>
        );
    }
}
