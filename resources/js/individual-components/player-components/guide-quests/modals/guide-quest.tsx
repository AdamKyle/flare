import React from "react";
import Dialogue from "../../../../game/components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import LoadingProgressBar from "../../../../game/components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../../game/components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../game/components/ui/alerts/simple-alerts/danger-alert";
import {buildValueLink, getRequirementKey, guideQuestLabelBuilder,} from "../lib/guide-quest-label-builder";
import RequiredListItem from "../components/required-list-item";
import {questRewardKeys} from "../lib/guide-quests-rewards";
import RewardListItem from "../components/reward-list-item";
import TabLayout from "../components/tab-labout";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";
import clsx from "clsx";
import GuideQuestProps from "./types/guide-quest-props";
import GuideQuestState from "./types/guide-quest-state";
import GuideQuestAjax, {GUIDE_QUEST_ACTIONS} from "../ajax/guide-quest-ajax";
import {guideQuestServiceContainer} from "../container/guide-quest-container";

enum EVENT_TYPE {
    WINTER_EVENT = 4,
}

export default class GuideQuest extends React.Component<GuideQuestProps, GuideQuestState> {

    private guideQuestAjax: GuideQuestAjax;

    constructor(props: GuideQuestProps) {
        super(props);

        this.state = {
            loading: true,
            action_loading: false,
            error_message: null,
            success_message: null,
            quest_data: null,
            can_hand_in: false,
            is_handing_in: false,
            completed_requirements: [],
        };

        this.guideQuestAjax = guideQuestServiceContainer().fetch(GuideQuestAjax);
    }

    componentDidMount() {

        this.guideQuestAjax.doGuideQuestAction(this, GUIDE_QUEST_ACTIONS.FETCH);
    }

    buildTitle() {
        if (this.state.loading) {
            return "One moment ...";
        }

        if (this.state.quest_data === null) {
            return "Guide Quest";
        }

        return this.state.quest_data.name;
    }

    closeMessage() {
        this.setState({
            success_message: null,
            error_message: null,
        });
    }

    handInQuest() {
        this.guideQuestAjax.doGuideQuestAction(this, GUIDE_QUEST_ACTIONS.HAND_IN);
    }

    fetchRequiredKeys(): string[] {

        if (this.state.quest_data === null) {
            return ['UNKNOWN']
        }

        return Object.keys(this.state.quest_data).filter((key: string) => {
            if (this.state.quest_data !== null) {
                return (
                    (key.startsWith("required_") || key.startsWith("secondary_")) &&
                    this.state.quest_data[key] !== null
                );
            }
        });
    }

    buildRequirementsList(): JSX.Element[] | [] {
        const requirementsList: JSX.Element[] = [];

        this.fetchRequiredKeys().forEach((key: string) => {
            if (this.state.quest_data === null) {
                return [];
            }

            let label = guideQuestLabelBuilder(key, this.state.quest_data);

            if (label !== null) {
                const requiredKey = getRequirementKey(key);
                const value = this.state.quest_data[requiredKey];
                const completedRequirements: string[] = this.state.completed_requirements || [];

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
                            this.state.quest_data
                        )}
                    />
                );
            }
        });

        return requirementsList;
    }

    buildRewardsItems(): JSX.Element[] | [] {
        const items: JSX.Element[] = [];

        questRewardKeys().forEach((key: string) => {
            if (this.state.quest_data === null) {
                return [];
            }

            if (this.state.quest_data[key] !== null) {
                const label = key
                    .split("_")
                    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(" ");

                items.push(
                    <RewardListItem
                        label={label}
                        value={this.state.quest_data[key]}
                    />
                );
            }
        });

        return items;
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.buildTitle()}
                secondary_actions={{
                    secondary_button_label: "Hand in",
                    secondary_button_disabled: !this.state.can_hand_in,
                    handle_action: this.handInQuest.bind(this),
                }}
                medium_modal={this.state.quest_data !== null}
                primary_button_disabled={this.state.action_loading}
            >
                {this.state.loading && this.state.quest_data === null ? (
                    <div className="p-5 mb-2">
                        <ComponentLoading />
                    </div>
                ) : this.state.quest_data === null ? (
                    <div className="my-4 text-orange-500 dark:text-orange-300">
                        <p>
                            You have completed all the current Guide Quests.
                            When new features are released there will be more
                            Guide Quests for you to walk you through new
                            features!
                        </p>
                    </div>
                ) : (
                    <div className="overflow-y-auto max-h-[450px] lg:max-h-none lg:overflow-visible">
                        <InfoAlert additional_css={clsx('my-4', {'hidden': this.state.quest_data.only_during_event === null && this.state.quest_data.unlock_at_level === null})}>
                            <p>
                                These types of Guide Quests only pop up during special events or when new features are unlocked at specific levels.
                                You can continue your regular guide quests once you finish this one and any "child" quests that might folow after it.
                            </p>
                        </InfoAlert>
                        {this.state.success_message !== null ? (
                            <SuccessAlert
                                close_alert={this.closeMessage.bind(this)}
                            >
                                {this.state.success_message}
                            </SuccessAlert>
                        ) : null}

                        {this.state.error_message !== null ? (
                            <DangerAlert
                                close_alert={this.closeMessage.bind(this)}
                            >
                                {this.state.error_message}
                            </DangerAlert>
                        ) : null}

                        <div className={"mt-2"}>
                            <div className="grid md:grid-cols-2 gap-2">
                                <div>
                                    <h3 className="mb-2">
                                        Required to complete
                                    </h3>
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

                        {this.state.quest_data.faction_points_per_kill !==
                        null ? (
                            <p className="text-blue-700 dark:text-blue-400">
                                You have been given an additional{" "}
                                {this.state.quest_data.faction_points_per_kill}{" "}
                                Faction Points per kill for this quest.
                            </p>
                        ) : null}

                        <TabLayout
                            intro_text={this.state.quest_data.intro_text}
                            instructions={this.state.quest_data.instructions}
                            desktop_instructions={
                                this.state.quest_data.desktop_instructions
                            }
                            mobile_instructions={
                                this.state.quest_data.mobile_instructions
                            }
                            is_small={this.props.view_port < 1600}
                        />

                        <p className={"mt-4 mb-4"}>
                            The Hand in button will become available when you
                            meet the requirements. Unless exploration is
                            running.
                        </p>
                        {this.state.is_handing_in ? (
                            <LoadingProgressBar />
                        ) : null}
                    </div>
                )}
            </Dialogue>
        );
    }
}
