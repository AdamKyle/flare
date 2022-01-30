import React from 'react';
import cloneDeep from 'lodash/cloneDeep';
import AlertInfo from "../components/base/alert-info";

export default class ServerMessages extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      messages: [],
      message: '',
      user: {},
    }

    this.serverMessages = Echo.private('server-message-' + this.props.userId);
  }

  componentDidMount() {

    this.serverMessages.listen('Game.Messages.Events.ServerMessageEvent', (event) => {
      if (!event.npc && !event.link) {
        const messages = cloneDeep(this.state.messages);

        const message = {
          message: event.message,
          type: 'server-message',
          user: event.user,
          user_id: event.user.id,
          id: Math.random().toString(36).substring(7),
          is_npc: event.npc,
          isLink: event.isLink,
          link: event.link,
          event_id: event.id,
        };

        messages.unshift(message);

        if (messages.length > 1000) {
          messages.length = 500; // Remove the last 500 messages to clear lag.
        }

        const user = cloneDeep(this.state.user);

        user.is_silenced = event.user.is_silenced;
        user.can_talk_again_at = event.user.can_speak_again_at;

        this.setState({
          messages: messages,
          user: user,
        });
      }
    });
  }

  renderMessages() {
    const elements = [];

    if (this.state.messages.length > 0) {

      this.state.messages.map((message) => {
        if (message.user_id === this.props.userId && message.type === 'server-message') {
          if (!message.is_npc) {
            elements.push(
              <li key={message.id + '_server-message'}>
                <div className="server-message">{message.message}</div>
              </li>
            )
          }
        }
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
                  <p>This is where all your rewards, skill level ups, gold dust messages, character level ups, crafting messages about success or fail,
                    enchanting and so on live. <strong>Anything that would generate a server message would live here.</strong> These use to be apart of chat, but chat got way too busy for the average player to see what
                    was going on and if any one was talking or not.</p>
                  <p>
                    If you are making use of Automation or crafting and enchanting, you'll want to pay attention to this tab.
                  </p>
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
