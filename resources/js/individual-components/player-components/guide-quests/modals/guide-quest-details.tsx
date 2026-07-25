import React from "react";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";
import SuccessAlert from "../../../../game/components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../game/components/ui/alerts/simple-alerts/danger-alert";
import WarningAlert from "../../../../game/components/ui/alerts/simple-alerts/warning-alert";
import TabLayout from "../components/tab-labout";
import clsx from "clsx";
import {
    buildValueLink,
    getRequirementKey,
    guideQuestLabelBuilder,
} from "../lib/guide-quest-label-builder";
import RequiredListItem from "../components/required-list-item";
import { questRewardKeys } from "../lib/guide-quests-rewards";
import RewardListItem from "../components/reward-list-item";
import GuideQuest from "../components/definitions/guide-quest";
import {
    CompletedGuideQuestRequirements,
    RequiredBatchCraftedItemRequirement,
} from "./types/guide-quest-state";

interface GuideQuestDetailsProps {
    guide_quest: GuideQuest;
    completed_requirements: CompletedGuideQuestRequirements[];
    close_message: () => void;
    success_message: string | null;
    error_message: string | null;
    view_port: number;
    read_only?: boolean;
    viewer_has_access?: boolean;
}

export default class GuideQuestDetails extends React.Component<GuideQuestDetailsProps> {
    constructor(props: GuideQuestDetailsProps) {
        super(props);
    }

    fetchRequiredKeys(): string[] {
        if (this.props.guide_quest === null) {
            return ["UNKNOWN"];
        }

        return Object.keys(this.props.guide_quest).filter((key: string) => {
            if (this.props.guide_quest !== null) {
                const value = this.props.guide_quest[key];

                if (value === null) {
                    return false;
                }

                if (Array.isArray(value) && value.length === 0) {
                    return false;
                }

                if (
                    key === "required_batch_crafting_hours" ||
                    key === "required_batch_crafted_item_names"
                ) {
                    return false;
                }

                return (
                    key.startsWith("required_") || key.startsWith("secondary_")
                );
            }

            return false;
        });
    }

    buildRequirementsList(): JSX.Element[] | [] {
        const requirementsList: JSX.Element[] = [];

        this.fetchRequiredKeys().forEach((key: string) => {
            if (this.props.guide_quest === null) {
                return [];
            }

            const matchingCompletedRequirements =
                this.props.completed_requirements.find(
                    (completedRequirements: CompletedGuideQuestRequirements) =>
                        completedRequirements.quest_id ===
                        this.props.guide_quest.id,
                );

            let completedRequirements: string[] = [];
            let batchCraftedItemRequirements: RequiredBatchCraftedItemRequirement[] =
                [];

            if (matchingCompletedRequirements !== undefined) {
                completedRequirements =
                    matchingCompletedRequirements.completed_requirements;
                batchCraftedItemRequirements =
                    matchingCompletedRequirements.required_batch_crafted_item_requirements;
            }

            if (key === "required_batch_crafted_items") {
                (
                    this.props.guide_quest.required_batch_crafted_item_names ??
                    []
                ).forEach((item) => {
                    const itemRequirement = batchCraftedItemRequirements.find(
                        (requirement) =>
                            requirement.requirement_index ===
                                item.requirement_index &&
                            requirement.item_id === item.item_id,
                    );
                    const itemLabel =
                        item.source === "alchemy_bag"
                            ? `Have ${item.amount}x ${item.name} of type ${item.type_name} in your alchemy bag. Current: ${itemRequirement?.current_amount ?? 0} / ${item.amount}.`
                            : `Have ${item.amount}x ${item.name} of type ${item.type_name} in your inventory ${item.must_be_enchanted ? "with both a prefix and a suffix" : "with no prefix or suffix"}. Current: ${itemRequirement?.current_amount ?? 0} / ${item.amount}.`;

                    requirementsList.push(
                        <RequiredListItem
                            key={`${key}-${item.requirement_index}-${item.item_id}`}
                            label={"Required Item"}
                            isFinished={itemRequirement?.is_complete ?? false}
                            requirement={itemLabel}
                        />,
                    );
                });

                requirementsList.push(
                    <RequiredListItem
                        key={`${key}-consumption`}
                        label={"Item Consumption"}
                        isFinished={false}
                        requirement={
                            "These items are consumed when the guide quest is handed in."
                        }
                    />,
                );

                return [];
            }

            let label = guideQuestLabelBuilder(key, this.props.guide_quest);

            if (label !== null) {
                const requiredKey = getRequirementKey(key);
                const value = this.props.guide_quest[requiredKey];

                const isFinished =
                    completedRequirements.includes(key) ||
                    completedRequirements.includes(requiredKey);

                requirementsList.push(
                    <RequiredListItem
                        key={key}
                        label={label}
                        isFinished={isFinished}
                        requirement={buildValueLink(
                            value,
                            key,
                            this.props.guide_quest,
                        )}
                    />,
                );
            }
        });

        return requirementsList;
    }

    buildRewardsItems(): JSX.Element[] | [] {
        const items: JSX.Element[] = [];

        questRewardKeys().forEach((key: string) => {
            if (this.props.guide_quest === null) {
                return [];
            }

            if (this.props.guide_quest[key] !== null) {
                const label = key
                    .split("_")
                    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(" ");

                items.push(
                    <RewardListItem
                        label={label}
                        value={this.props.guide_quest[key]}
                    />,
                );
            }
        });

        return items;
    }

    render() {
        return (
            <>
                {this.props.read_only && !this.props.viewer_has_access ? (
                    <WarningAlert additional_css={"my-4"}>
                        You have not completed this guide quest yet.
                    </WarningAlert>
                ) : null}
                <InfoAlert
                    additional_css={clsx("my-4", {
                        hidden:
                            this.props.guide_quest.only_during_event === null &&
                            this.props.guide_quest.unlock_at_level === null,
                    })}
                >
                    <p>
                        These types of Guide Quests only pop up during special
                        events or when new features are unlocked at specific
                        levels. You can continue your regular guide quests once
                        you finish this one and any "child" quests that might
                        folow after it.
                    </p>
                </InfoAlert>
                {this.props.success_message !== null ? (
                    <SuccessAlert close_alert={this.props.close_message}>
                        {this.props.success_message}
                    </SuccessAlert>
                ) : null}

                {this.props.error_message !== null ? (
                    <DangerAlert close_alert={this.props.close_message}>
                        {this.props.error_message}
                    </DangerAlert>
                ) : null}

                <div className={"mt-2"}>
                    <div className="grid md:grid-cols-2 gap-2">
                        <div>
                            <h3 className="mb-2">Required to complete</h3>
                            <ul className="my-4 list-disc ml-[18px]">
                                {this.buildRequirementsList()}
                            </ul>
                        </div>
                        <div className="block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                        <div>
                            <h3 className="mb-2">Rewards</h3>
                            <ul className="list-disc ml-[18px]">
                                {this.buildRewardsItems()}
                            </ul>
                        </div>
                    </div>
                </div>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>

                {this.props.guide_quest.faction_points_per_kill !== null ? (
                    <p className="text-blue-700 dark:text-blue-400">
                        You have been given an additional{" "}
                        {this.props.guide_quest.faction_points_per_kill} Faction
                        Points per kill for this quest.
                    </p>
                ) : null}

                <TabLayout
                    intro_text={this.props.guide_quest.intro_text}
                    instructions={this.props.guide_quest.instructions}
                    desktop_instructions={
                        this.props.guide_quest.desktop_instructions
                    }
                    mobile_instructions={
                        this.props.guide_quest.mobile_instructions
                    }
                    is_small={this.props.view_port < 1600}
                />

                {!this.props.read_only ? (
                    <>
                        <p className={"mt-4 mb-4"}>
                            The Hand in button will become available when you
                            meet the requirements. Unless exploration is
                            running.
                        </p>

                        <p className={"mt-4 mb-4"}>
                            You can click the top right button in the header
                            called Guide Quests to re-open this modal. You can
                            also see previous Guide Quests by opening the top
                            left menu, selecting Quest Log and then selecting
                            Completed Guide Quests.
                        </p>
                    </>
                ) : null}
            </>
        );
    }
}
