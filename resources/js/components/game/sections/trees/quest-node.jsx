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

  render() {
    return (
      <div>
        <strong>
          <a href="#" onClick={this.manageQuestDetails.bind(this)}>
            {this.props.quest.name}
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