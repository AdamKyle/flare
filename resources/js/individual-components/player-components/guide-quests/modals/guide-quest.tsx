import React from "react";
import Dialogue from "../../../../game/components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import LoadingProgressBar from "../../../../game/components/ui/progress-bars/loading-progress-bar";
import GuideQuestProps from "./types/guide-quest-props";
import GuideQuestState from "./types/guide-quest-state";
import GuideQuestAjax, { GUIDE_QUEST_ACTIONS } from "../ajax/guide-quest-ajax";
import { guideQuestServiceContainer } from "../container/guide-quest-container";
import GuideQuestDetails from "./guide-quest-details";
import PrimaryOutlineButton from "../../../../game/components/ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../../../game/components/ui/buttons/success-outline-button";
import SuccessAlert from "../../../../game/components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../game/components/ui/alerts/simple-alerts/danger-alert";

enum EVENT_TYPE {
    WINTER_EVENT = 4,
}

export default class GuideQuest extends React.Component<
    GuideQuestProps,
    GuideQuestState
> {
    private guideQuestAjax: GuideQuestAjax;

    constructor(props: GuideQuestProps) {
        super(props);

        this.state = {
            loading: true,
            action_loading: false,
            error_message: null,
            success_message: null,
            quest_data: [],
            can_hand_in: [],
            is_handing_in: false,
            completed_requirements: [],
            selected_quest_data_to_show: null,
        };

        this.guideQuestAjax =
            guideQuestServiceContainer().fetch(GuideQuestAjax);
    }

    componentDidMount() {
        console.log('Huh?');
        this.guideQuestAjax.doGuideQuestAction(this, GUIDE_QUEST_ACTIONS.FETCH);
    }

    componentDidUpdate(prevProps: GuideQuestProps, prevState: GuideQuestState) {
        console.log(prevState, this.state);
    }

    buildTitle() {

        if (this.state.loading) {
            return "One moment ...";
        }

        if (this.state.quest_data === null) {
            return "Guide Quest";
        }

        if (this.state.quest_data.length === 0) {
            return "Guide Quest";
        }

        if (this.state.selected_quest_data_to_show === null) {
            return "Guide Quests";
        }

        return this.state.selected_quest_data_to_show.name;
    }

    closeMessage() {
        this.setState({
            success_message: null,
            error_message: null,
        });
    }

    handInQuest() {
        this.guideQuestAjax.doGuideQuestAction(
            this,
            GUIDE_QUEST_ACTIONS.HAND_IN,
        );
    }

    canHandInQuest() {
        if (this.state.selected_quest_data_to_show === null) {
            return false;
        }

        return (
            this.state.can_hand_in.filter((canHandIn: any) => {
                return (
                    canHandIn.quest_id ===
                    this.state.selected_quest_data_to_show?.id &&
                        canHandIn.can_hand_in
                );
            }).length > 0
        );
    }

    showQuest(quest: any) {
        this.setState({
            selected_quest_data_to_show: quest,
        })
    }

    viewQuests() {
        this.setState({
            selected_quest_data_to_show: null,
        })
    }

    renderButtons(): JSX.Element[] | [] {
        if (this.state.quest_data === null) {
            return [];
        }

        return this.state.quest_data.map((questData: any) => {

            const isQuestComplete = this.state.can_hand_in.filter((canHandIn: any) => {
                return canHandIn.quest_id === questData.id && canHandIn.can_hand_in;
            }).length > 0

            if (questData.unlock_at_level > 0) {
                return (
                    <div className={"w-full my-2"}>
                        <SuccessOutlineButton
                            button_label={"New Feature: " + questData.name + ' ' + (isQuestComplete ? ' ✓' : '')}
                            on_click={() => {
                                this.showQuest(questData);
                            }}
                            additional_css={'w-full'}
                        />
                    </div>
                );
            }

            if (questData.only_during_event !== null) {
                return (
                    <div className={"w-full my-2"}>
                        <SuccessOutlineButton
                            button_label={"New Feature: " + questData.name + ' ' + (isQuestComplete ? ' ✓' : '')}
                            on_click={() => {
                                this.showQuest(questData);
                            }}
                            additional_css={'w-full'}
                        />
                    </div>
                );
            }

            return (
                <div className={"w-full"}>
                <PrimaryOutlineButton
                        button_label={"Next Quest: " + questData.name + ' ' + (isQuestComplete ? ' ✓' : '')}
                        on_click={() => {
                            this.showQuest(questData);
                        }}
                        additional_css={'w-full'}
                    />
                </div>
            );
        });
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.buildTitle() + " [GUIDE QUEST]"}
                secondary_actions={{
                    secondary_button_label: "Hand in",
                    secondary_button_disabled: !this.canHandInQuest(),
                    handle_action: this.handInQuest.bind(this),
                }}
                tertiary_actions={{
                    tertiary_button_label: 'View Quests',
                    handle_action: this.viewQuests.bind(this),
                    tertiary_button_disabled: this.state.quest_data.length <= 1 || this.state.selected_quest_data_to_show === null,
                }}
                medium_modal={this.state.quest_data.length > 0}
                primary_button_disabled={this.state.action_loading}
            >
                {this.state.loading ? (
                    <div className="p-5 mb-2">
                        <ComponentLoading />
                    </div>
                ) : this.state.quest_data.length === 0 ? (
                    <div className="my-4 text-orange-500 dark:text-orange-300">
                        <p>
                            You have completed all the current Guide Quests.
                            When new features are released there will be more
                            Guide Quests for you to walk you through new
                            features!
                        </p>
                    </div>
                ) : (
                    <div>
                        {this.state.selected_quest_data_to_show !== null ? (
                            <GuideQuestDetails
                                guide_quest={
                                    this.state.selected_quest_data_to_show
                                }
                                completed_requirements={
                                    this.state.completed_requirements
                                }
                                close_message={this.closeMessage.bind(this)}
                                success_message={this.state.success_message}
                                error_message={this.state.error_message}
                                view_port={this.props.view_port}
                            />
                        ) : (
                            <div>
                                {this.state.success_message !== null ? (
                                    <SuccessAlert close_alert={this.closeMessage.bind(this)}>
                                        {this.state.success_message}
                                    </SuccessAlert>
                                ) : null}

                                {this.state.error_message !== null ? (
                                    <DangerAlert close_alert={this.closeMessage.bind(this)}>
                                        {this.state.error_message}
                                    </DangerAlert>
                                ) : null}

                                <div className={"mr-auto ml-auto w-full md:w-1/2 "}>
                                    <p className="mb-2">
                                        Below are two or more quests for you to do. There are two types: Regular and New
                                        Feature.
                                    </p>
                                    <p className="mb-2">
                                        <strong>New Features</strong> are those that Tlessa has released and players can
                                        begin to dive into
                                        them in a guided way.
                                    </p>
                                    <p className="mb-2">
                                        <strong>Regular</strong> quests are those that advance your learning of Tlessa
                                        and
                                        it's various
                                        mechanics.
                                    </p>
                                    <p className="mb-2">
                                        You can choose which ever to do. If you are new, I would recommend continuing
                                        with
                                        the Regular Quests.
                                    </p>
                                    <p className="mb-2">
                                        If you see a checkmark beside the name of the quest, it means it's complete and
                                        you can hand it in.
                                    </p>
                                    <div
                                        className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>
                                    {this.renderButtons()}
                                </div>
                            </div>

                        )}

                        <div className="overflow-y-auto max-h-[450px] lg:max-h-none lg:overflow-visible">
                            {this.state.is_handing_in ? (
                                <LoadingProgressBar />
                            ) : null}
                        </div>
                    </div>
                )}
            </Dialogue>
        );
    }
}
