import React from "react";
import QuestNodeProps from "../../../map/types/map/quests/quest-node-props";
import QuestDetailsModal from "../modals/quest-details-modal";
import clsx from "clsx";

export default class QuestNode extends React.Component<QuestNodeProps, any> {
    constructor(props: QuestNodeProps) {
        super(props);

        this.state = {
            open_quest_modal: false,
        };
    }

    showQuestDetails() {
        this.setState({
            open_quest_modal: !this.state.open_quest_modal,
        });
    }

    isQuestCompleted() {
        if (this.props.quest !== null) {
            // @ts-ignore
            return this.props.completed_quests.includes(this.props.quest.id);
        }

        return false;
    }

    isAllRequiredQuestsInChainComplete() {
        if (this.props.quest !== null) {
            if (this.props.quest.required_quest_chain_details !== null) {
                const requiredQuests =
                    this.props.quest.required_quest_chain_details.quest_ids;
                const completedQuests = new Set(this.props.completed_quests);

                return requiredQuests.every((requiredQuest: number) =>
                    completedQuests.has(requiredQuest),
                );
            }
        }

        return true;
    }

    isParentQuestComplete() {
        if (this.props.quest !== null) {
            if (this.props.quest.is_parent) {
                return true;
            }

            return this.props.completed_quests.includes(
                // @ts-ignore
                this.props.quest.parent_quest_id as number,
            );
        }

        return false;
    }

    isRequiredQuestComplete(): boolean {
        if (this.props.quest !== null) {
            if (this.props.quest.required_quest_id !== null) {
                const completedQuests = this.props.completed_quests as number[];
                return completedQuests.includes(
                    this.props.quest.required_quest_id,
                );
            }
        }
        return true;
    }

    render() {
        if (this.props.quest?.name === "Searching for a coin") {
            console.log(
                !this.isRequiredQuestComplete(),
                !this.isAllRequiredQuestsInChainComplete(),
                this.isParentQuestComplete(),
            );
        }
        return (
            <div>
                <button
                    type="button"
                    role="button"
                    className={clsx(
                        {
                            "text-green-700 dark:text-green-600":
                                this.isQuestCompleted(),
                        },
                        {
                            "text-red-500 dark:text-red-400":
                                !this.isQuestCompleted() &&
                                !this.isParentQuestComplete(),
                        },
                        {
                            "text-yellow-700 dark:text-yellow-600":
                                !this.isQuestCompleted() &&
                                this.isParentQuestComplete() &&
                                (!this.isRequiredQuestComplete() ||
                                    !this.isAllRequiredQuestsInChainComplete()),
                        },
                        {
                            "text-blue-500 dark:text-blue-400":
                                !this.isQuestCompleted() &&
                                this.isParentQuestComplete() &&
                                this.isRequiredQuestComplete() &&
                                this.isAllRequiredQuestsInChainComplete(),
                        },
                    )}
                    onClick={this.showQuestDetails.bind(this)}
                >
                    {this.props.quest?.name}
                </button>

                {this.state.open_quest_modal ? (
                    <QuestDetailsModal
                        is_open={this.state.open_quest_modal}
                        handle_close={this.showQuestDetails.bind(this)}
                        quest_id={this.props.quest?.id}
                        character_id={this.props.character_id}
                        is_parent_complete={this.isParentQuestComplete()}
                        is_quest_complete={this.isQuestCompleted()}
                        is_required_quest_complete={this.isRequiredQuestComplete()}
                        is_required_quest_chain_complete={this.isAllRequiredQuestsInChainComplete()}
                        required_quest_chain_details={
                            this.props.quest?.required_quest_chain_details
                        }
                        completed_quests={this.props.completed_quests}
                        update_quests={this.props.update_quests}
                    />
                ) : null}
            </div>
        );
    }
}
