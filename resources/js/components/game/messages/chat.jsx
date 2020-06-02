import React from 'react';
import cloneDeep from 'lodash/cloneDeep';
import uniqBy from 'lodash/uniqBy';
import {getServerMessage} from '../helpers/server_message'

export default class Chat extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      messages: [],
      serverMessages: [],
      message: '',
    }

    this.echo            = Echo.join('chat');
    this.serverMessages  = Echo.private('server-message-' + this.props.userId);
    this.privateMessages = Echo.private('private-message-' + this.props.userId);

    // this.globalMessages = Echo.join('global-messages');
    // this.dropMessage = Echo.private('drop-message-' + this.props.userId);
  }

  componentDidMount() {
    this.echo.listen('Game.Messages.Events.MessageSentEvent', (event) => {
      const message    = event.message;
      message['user']  = event.user;
      message['name']  = event.name;

      const messages = cloneDeep(this.state.messages);

      messages.unshift(message);

      this.setState({
        messages: messages
      });
    });

    this.serverMessages.listen('Game.Messages.Events.ServerMessageEvent', (event) => {
      const messages = cloneDeep(this.state.messages);
      const message  = {
        message: event.message,
        type:    'server-message',
        user:    event.user,
        user_id: event.user.id,
        id:      Math.random().toString(36).substring(7),
      };

      messages.unshift(message);

      this.setState({
        messages: messages
      });
    });

    this.privateMessages.listen('Game.Messages.Events.PrivateMessageEvent', (event) => {
      const messages = cloneDeep(this.state.messages);
      const message  = {
        message: event.message,
        type:    'private-message',
        user:    event.user,
        user_id: event.user.id,
        from:    event.from,
        id:      Math.random().toString(36).substring(7),
      };

      messages.unshift(message);

      this.setState({
        messages: messages
      });
    });
  }

  componentWillUnMount() {
    Echo.leave('chat');
  }

  messageUser(e) {
    const name = e.target.getAttribute('data-name');

    this.setState({
      message: '/m ' + name + ': '
    }, () => {
      this.chatInput.focus();
    });
  }

  rendermessages() {
    const elements = [];

    if (this.state.messages.length > 0) {

      this.state.messages.map((message) => {
        if (message.user_id === this.props.userId && message.type === 'server-message') {
          elements.push(
            <li key={message.id + '_server-message'}>
              <div className="server-message">{message.message}</div>
            </li>
          )
        } else if (message.user_id === this.props.userId && message.type === 'private-message') {
          elements.push(
            <li key={message.id + '_private-message'}>
              <div className="private-message">
                <strong onClick={this.messageUser.bind(this)} data-name={message.from}>{message.from}</strong>: {message.message}
              </div>
            </li>
          )
        } else if (message.user_id === this.props.userId && message.type === 'drop-message') {
          elements.push(
            <li key={message.id + 'drop-message'}>
              <div className="drop-message">{message.message}</div>
            </li>
          )
        } else if (message.type === 'global-message') {
          elements.push(
            <li key={message.id + '_global-message'}>
              <div className="global-message">
                <h3 className="global-message">{message.message}</h3>
              </div>
            </li>
          )
        } else if (message.from_god) {
          elements.push(
            <li key={message.id + '_god-message'}>
              <div className="god-message">
                <strong>GOD</strong> {message.message}
              </div>
            </li>
          )
        } else {
          elements.push(
            <li key={message.id}>
              <div className="message">
                <strong onClick={this.messageUser.bind(this)} data-name={message.name}>{message.name}</strong> {message.message}
              </div>
            </li>
          )
        }
      });
    }

    return elements;
  }

  postMessage() {
    axios.post('api/public-message', {
      message: this.state.message.replace(/(<([^>]+)>)/ig,"")
    }).then((result) => {
      this.setState({
        message: ''
      });
    }).catch((error) => {
      console.log(error);
    });
  }

  postPrivateMessage() {
    const messageData = this.state.message.match(/^\/m\s+(\w+[\w| ]*):\s*(.*)/)

    if (messageData == null) {
      getServerMessage('invalid_command');
      return;
    }
    
    axios.post('/api/private-message', {
      user_name: messageData[1],
      message: messageData[2],
    }).then((result) => {
      this.setState({
        message: ''
      });
    }).catch((error) => {
      console.log(error);
    });
  }

  handleKeyPress(e) {
    if (e.key === 'Enter') {
      e.target.value = '';

      if (this.state.message.length < 1) {
        getServerMessage('message_length_0');
      } else if (this.state.message.length > 140) {
        getServerMessage('message_length_max');
      } else {
        if (this.state.message.includes('/m')) {
          this.postPrivateMessage()
        } else {
          this.postMessage();
        }
      }
    }
  }

  handleOnClick() {
    this.postMessage();
  }

  handleOnChange(e) {
    this.setState({
      message: e.target.value
    });
  }

  render() {
    return (
      <div className="card p-2 pr-4">
        <div className="card-body">
          <div className="chat">
            <div className="row">
              <div className="col-11">
                <input
                  type="text"
                  className="form-control input-sm"
                  value={this.state.message}
                  onChange={this.handleOnChange.bind(this)}
                  onKeyPress={this.handleKeyPress.bind(this)}
                  ref={(input) => { this.chatInput = input; }} 
                />
              </div>

              <div className="col-1">
                <button className="btn btn-primary" onClick={this.handleOnClick.bind(this)}>Send</button>
              </div>
            </div>

            <div className="row">
              <div className="col-md-12">
                <div className="chat-box mt-3 pt-3 pl-3">
                  <ul> { this.rendermessages() }</ul>
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
