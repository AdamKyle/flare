import React from 'react';
import QuestDetails from "../modals/quest-details";

export default class QuestNode extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      openQuestDetails: false,
    }
  }

  manageQuestDetails() {
    this.setState({
      openQuestDetails: !this.state.openQuestDetails
    });
  }

  hasQuestBeenCompleted() {
    return this.props.completedQuests.includes(this.props.quest.id);
  }

  isQuestLocked() {
    const hasCompletedParent = this.props.completedQuests.includes(this.props.quest.parent_quest_id);
    const isChildSkill       = this.props.quest.parent_quest_id !== null;

    return !hasCompletedParent && isChildSkill;
  }

  render() {
    return (
      <div>
        <strong>
          <a href="#" className={this.hasQuestBeenCompleted() ? 'text-success' : this.isQuestLocked() ? 'text-danger' : null}onClick={this.manageQuestDetails.bind(this)}>
            {this.props.quest.name} {this.hasQuestBeenCompleted() ? <i className="fas fa-check text-success"></i> : this.isQuestLocked() ?
            <i className="fas fa-lock"></i> : null}
          </a>
        </strong>

        {
          this.state.openQuestDetails ?
            <QuestDetails
              quest={this.props.quest}
              show={this.state.openQuestDetails}
              questDetailsClose={this.manageQuestDetails.bind(this)}
            />
          :
            null
        }
      </div>
    )
  }
}