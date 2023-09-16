import React, { Fragment } from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../lib/ajax/ajax";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import {
    guideQuestLabelBuilder,
    getRequirementKey,
    buildValueLink,
} from "../lib/guide-quest-label-builder";
import RequiredListItem from "../components/required-list-item";
import { questRewardKeys } from "../lib/guide-quests-rewards";
import RewardListItem from "../components/reward-list-item";

export default class GuideQuest extends React.Component<any, any> {
    private tabs: { name: string; key: string }[];

    constructor(props: any) {
        super(props);

        this.tabs = [
            {
                key: "story",
                name: "Story",
            },
            {
                key: "instructions",
                name: "Instructions",
            },
            {
                key: "desktop-instructions",
                name: "Desktop Instructions",
            },
            {
                key: "mobile-instructions",
                name: "Mobile Instructions",
            },
        ];

        this.state = {
            loading: true,
            error_message: null,
            success_message: null,
            quest_data: null,
            can_hand_in: false,
            is_handing_in: false,
            completed_requirements: [],
        };
    }

    componentDidMount() {
        new Ajax()
            .setRoute("character/guide-quest/" + this.props.user_id)
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        quest_data: result.data.quest,
                        can_hand_in: result.data.can_hand_in,
                        completed_requirements:
                            result.data.completed_requirements,
                    });
                },
                (error: AxiosError) => {
                    if (typeof error.response !== "undefined") {
                        const response = error.response;

                        this.setState({
                            error_message: response.data.message,
                            is_handing_in: false,
                        });
                    }
                }
            );
    }

    buildTitle() {
        if (this.state.loading) {
            return "One moment ...";
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
        this.setState(
            {
                is_handing_in: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "guide-quests/hand-in/" +
                            this.props.user_id +
                            "/" +
                            this.state.quest_data.id
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                is_handing_in: false,
                                quest_data: result.data.quest,
                                can_hand_in: result.data.can_hand_in,
                                success_message: result.data.message,
                                completed_requirements:
                                    result.data.completed_requirements,
                            });
                        },
                        (error: AxiosError) => {}
                    );
            }
        );
    }

    fetchRequiredKeys(): string[] {
        return Object.keys(this.state.quest_data).filter((key: string) => {
            return (
                (key.startsWith("required_") || key.startsWith("secondary_")) &&
                this.state.quest_data[key] !== null
            );
        });
    }

    buildRequirementsList(): JSX.Element[] | [] {
        const requirementsList: JSX.Element[] = [];

        this.fetchRequiredKeys().forEach((key: string) => {
            let label = guideQuestLabelBuilder(key, this.state.quest_data);

            if (label !== null) {
                const requiredKey = getRequirementKey(key);
                const value = this.state.quest_data[requiredKey];
                const completedRequirements = this.state.completed_requirements;

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
                large_modal={true}
                primary_button_disabled={this.state.action_loading}
            >
                {this.state.loading && this.state.quest_data === null ? (
                    <div className="p-5 mb-2">
                        <ComponentLoading />
                    </div>
                ) : (
                    <div className="overflow-y-auto max-h-[450px] lg:max-h-none lg:overflow-visible">
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

                        <Tabs tabs={this.tabs}>
                            <TabPanel key={"story"}>
                                <div
                                    className={
                                        "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4"
                                    }
                                >
                                    <div
                                        dangerouslySetInnerHTML={{
                                            __html: this.state.quest_data
                                                .intro_text,
                                        }}
                                    />
                                </div>
                            </TabPanel>
                            <TabPanel key={"instructions"}>
                                <div
                                    className={
                                        "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4 guide-quest-instructions"
                                    }
                                >
                                    <div
                                        dangerouslySetInnerHTML={{
                                            __html: this.state.quest_data
                                                .instructions,
                                        }}
                                    />
                                </div>
                            </TabPanel>
                            <TabPanel key={"desktop-instructions"}>
                                 <div
                                    className={
                                        "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4 guide-quest-instructions"
                                    }
                                >
                                    <div
                                        dangerouslySetInnerHTML={{
                                            __html: this.state.quest_data
                                                .desktop_instructions,
                                        }}
                                    />
                                </div>
                            </TabPanel>
                            <TabPanel key={"mobile-instructions"}>
                                <div
                                    className={
                                        "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4 guide-quest-instructions"
                                    }
                                >
                                    <div
                                        dangerouslySetInnerHTML={{
                                            __html: this.state.quest_data
                                                .mobile_instructions,
                                        }}
                                    />
                                </div>
                            </TabPanel>
                        </Tabs>
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
