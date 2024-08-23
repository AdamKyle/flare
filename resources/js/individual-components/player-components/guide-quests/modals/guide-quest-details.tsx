import React from "react";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";
import SuccessAlert from "../../../../game/components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../game/components/ui/alerts/simple-alerts/danger-alert";
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

interface GuideQuestDetailsProps {
    guide_quest: GuideQuest;
    completed_requirements: string[] | [];
    close_message: () => void;
    success_message: string | null;
    error_message: string | null;
    view_port: number;
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
                return (
                    (key.startsWith("required_") ||
                        key.startsWith("secondary_")) &&
                    this.props.guide_quest[key] !== null
                );
            }
        });
    }

    buildRequirementsList(): JSX.Element[] | [] {
        const requirementsList: JSX.Element[] = [];

        this.fetchRequiredKeys().forEach((key: string) => {
            if (this.props.guide_quest === null) {
                return [];
            }

            let label = guideQuestLabelBuilder(key, this.props.guide_quest);

            if (label !== null) {
                const requiredKey = getRequirementKey(key);
                const value = this.props.guide_quest[requiredKey];

                const matchingCompletedRequirements: any = this.props.completed_requirements.filter((completedRequirements: any) => {
                    return completedRequirements.quest_id === this.props.guide_quest.id
                });

                let completedRequirements: string[] = [];

                if (matchingCompletedRequirements.length > 0) {
                    completedRequirements = matchingCompletedRequirements[0].completed_requirements;
                }

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

                <p className={"mt-4 mb-4"}>
                    The Hand in button will become available when you meet the
                    requirements. Unless exploration is running.
                </p>

                <p className={"mt-4 mb-4"}>
                    You can click the top right button in the header called
                    Guide Quests to re-open this modal. You can also see
                    previous Guide Quests by opening the top left menu,
                    selecting Quest Log and then selecting Completed Guide
                    Quests.
                </p>
            </>
        );
    }
}
