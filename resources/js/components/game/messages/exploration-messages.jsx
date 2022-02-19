import React from 'react';
import cloneDeep from 'lodash/cloneDeep';
import AlertInfo from "../components/base/alert-info";

export default class ExplorationMessages extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      messages: [],
      message: '',
      user: {},
    }

    this.serverMessages = Echo.private('exploration-log-update-' + this.props.userId);
  }

  componentDidMount() {

    this.serverMessages.listen('Game.Exploration.Events.ExplorationLogUpdate', (event) => {
      const messages = cloneDeep(this.state.messages);

      const message = {
        message: event.message,
        makeItalic: event.makeItalic,
        isReward: event.isReward,
        id: Math.random().toString(36).substring(7)
      };

      messages.unshift(message);

      if (messages.length > 1000) {
        messages.length = 500; // Remove the last 500 messages to clear lag.
      }

      this.setState({
        messages: messages,
      }, () => {
        this.props.updateChatTabIcon();
      });
    });
  }

  makeItalicClass(message) {
    return message.makeItalic ? 'make-italic' : ' '
  }

  rewardCSS(message) {
    return message.isReward ? 'bonus-reward' : ' '
  }

  renderMessages() {
    const elements = [];

    if (this.state.messages.length > 0) {

      this.state.messages.map((message) => {
        elements.push(
          <li key={message.id + '_exploration-message'}>
            <div className={'exploration-message ' + this.makeItalicClass(message) + this.rewardCSS(message)}>{message.message}</div>
          </li>
        )
      });
    }

    return elements;
  }

  render() {
    return (
      <div className="card p-2 pr-4">
        <div className="card-body">
          <div className="chat">
            <div className="row">
              <div className="col-md-12">
                <AlertInfo icon={"fas fa-question-circle"} title={"What is this?"}>
                  <p>When you are logged in and exploring, every ten minutes this section will update to show you the process of each encounter. Encounters happen every 10 minutes.</p>
                </AlertInfo>
                <div className="chat-box mt-3">
                  <ul> {this.renderMessages()}</ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    )

    return null;
  }
}
