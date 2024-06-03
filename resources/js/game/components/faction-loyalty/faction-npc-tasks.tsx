import React, { ReactNode } from "react";
import FactionNpcSectionProps from "./types/faction-npc-section-props";
import OrangeProgressBar from "../../components/ui/progress-bars/orange-progress-bar";
import { FameTasks } from "./deffinitions/faction-loaylaty";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
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
        };

        this.fightAjax = serviceContainer().fetch(BountyFightAjax);

        this.craftingAjax = serviceContainer().fetch(HandleCraftingAjax);
    }

    bountyTask(monsterId?: number) {
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
                    },
                    this.props.character_id,
                );
            },
        );
    }

    craftingTask(itemType: string, itemId?: number) {
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
            !(
                this.props.faction_loyalty_npc.npc.game_map_id ===
                this.props.character_map_id
            ) ||
            !this.props.faction_loyalty_npc.currently_helping
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
                                        disabled={
                                            !this.props.can_craft ||
                                            this.state.crafting ||
                                            this.state.must_revive ||
                                            fameTask.current_amount ===
                                                fameTask.required_amount ||
                                            !this.props.faction_loyalty_npc
                                                .currently_helping
                                        }
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

    renderTaskSection(): ReactNode {
        return (
            <div>
                <div>
                    <h3 className="my-2"> Bounties </h3>
                    {this.props.character_map_id !==
                    this.props.faction_loyalty_npc.npc.game_map_id ? (
                        <WarningAlert additional_css={"my-2"}>
                            You are not on the same place as this NPC, you
                            cannot take part in the bounty tasks.
                        </WarningAlert>
                    ) : null}
                    <InfoAlert additional_css={"my-2"}>
                        You attack type, when doing bounties via this tab, will
                        be: <strong>{this.props.attack_type}</strong>
                    </InfoAlert>
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
                    <div>
                        {this.renderTaskSection()}
                        <p className="my-4">
                            Bounties must be completed on the respective plane
                            and manually. Automation will not work for this.
                        </p>
                    </div>
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
