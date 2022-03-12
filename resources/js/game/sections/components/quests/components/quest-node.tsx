import React from "react";
import QuestNodeProps from "../../../../lib/game/types/map/quests/quest-node-props";
import QuestDetailsModal from "../modals/quest-details-modal";

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

    render() {
        return (
            <div>
                <button type='button' role='button' className={'text-blue-500 dark:text-blue-400'} onClick={this.showQuestDetails.bind(this)}>{this.props.quest?.name}</button>

                {
                    this.state.open_quest_modal ?
                        <QuestDetailsModal is_open={this.state.open_quest_modal} handle_close={this.showQuestDetails.bind(this)} quest_id={this.props.quest?.id} character_id={this.props.character_id} />
                    : null
                }
            </div>
        );
    }
}
