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

export default class FactionNpcTasks extends React.Component<
    FactionNpcSectionProps,
    any
> {
    private readonly fightAjax?: BountyFightAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            attacking: false,
            success_message: null,
            error_message: null,
            must_revive: false,
        };

        this.fightAjax = serviceContainer().fetch(BountyFightAjax);
    }

    bountyTask(monsterId?: number) {
        if (!this.fightAjax) {
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
                if (!this.fightAjax) {
                    return;
                }

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
                                        on_click={() => {}}
                                        disabled={!this.props.can_craft}
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
                    </div>
                </div>
                <p className="my-4">
                    Bounties must be completed on the respective plane and
                    manually. Automation will not work for this.
                </p>
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
