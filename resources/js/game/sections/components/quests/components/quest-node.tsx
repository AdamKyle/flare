import React from "react";
import QuestNodeProps from "../../../../lib/game/types/map/quests/quest-node-props";
import QuestDetailsModal from "../modals/quest-details-modal";
import clsx from "clsx";

export default class QuestNode extends React.Component<QuestNodeProps, any> {

    constructor(props: QuestNodeProps) {
        super(props);

        this.state = {
            open_quest_modal: false,
        }
    }

    showQuestDetails() {
        this.setState({
            open_quest_modal: !this.state.open_quest_modal
        });
    }

    isQuestCompleted() {
        if (this.props.quest !== null) {
            // @ts-ignore
            return this.props.completed_quests.includes(this.props.quest.id)
        }

        return false
    }

    isParentQuestComplete() {
        if (this.props.quest !== null) {

            if (this.props.quest.is_parent) {
                return this.isQuestCompleted();
            }

            // @ts-ignore
            return this.props.completed_quests.includes(this.props.quest.parent_quest_id)
        }

        return false
    }

    render() {
        return (
            <div>
                <button type='button' role='button' className={clsx({
                    'text-blue-500 dark:text-blue-400': !this.isQuestCompleted() && this.isParentQuestComplete()
                }, {
                    'text-green-700 dark:text-green-600': this.isQuestCompleted()
                }, {
                    'text-red-500 dark:text-red-400': !this.isParentQuestComplete()
                })} onClick={this.showQuestDetails.bind(this)}>{this.props.quest?.name}</button>

                {
                    this.state.open_quest_modal ?
                        <QuestDetailsModal is_open={this.state.open_quest_modal} handle_close={this.showQuestDetails.bind(this)} quest_id={this.props.quest?.id} character_id={this.props.character_id} is_complete={this.isParentQuestComplete()}/>
                    : null
                }
            </div>
        );
    }
}
