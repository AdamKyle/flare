import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';
import Card from "../game/components/templates/card";
import ChatMessageActions from "./modal/ChatMessageActions";

export default class ChatMessages extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      messages: [],
      loading: true,
      message: null,
      openChatActions: false,
    }

    this.chat_message_config = {
      page_size: 5,
      length_menu: [5, 10, 15],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.chat_message_column = [
      {
        name: "character-name",
        text: "Character Name",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.character_name}</div>
        </div>,
      },
      {
        name: "is-banned",
        text: "Is Banned",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.user.is_banned ? 'Yes' : 'No'}</div>
        </div>,
      },
      {
        name: "is-silenced",
        text: "Is Silenced",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.user.is_silenced ? 'Yes' : 'No'}</div>
        </div>,
      },
      {
        name: "to-character",
        text: "To Character",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.to_character}</div>
        </div>,
      },
      {
        name: 'from-character',
        text: 'From Character',
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.from_character}</div>
        </div>,
      },
      {
        key: "private",
        text: "Private Message?",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.is_private}</div>
        </div>,
      },
      {
        name: "message",
        text: "Message",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.message}</div>
        </div>,
      },
    ];

    this.refreshMessages = Echo.private('refresh-messages-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/admin/chat-messages').then((result) => {
      this.setState({
        messages: result.data,
        loading: false,
      });
    }).catch((err) => {
      console.error(err);
    });

    this.refreshMessages.listen('Admin.Events.UpdateAdminChatEvent', (event) => {
      this.setState({
        messages: event.messages,
      });
    });
  }

  chatAction(event, data) {
    this.setState({
      message: data,
      openChatActions: true,
    });
  }

  closeAction() {
    this.setState({
      message: null,
      openChatActions: false,
    })
  }

  render() {
    return (
      <Card title={"Messages"} otherClasses={'overflow-table'}>
        {
          this.state.loading ?
            <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
            :
            <>
              <ReactDatatable
                config={this.chat_message_config}
                records={this.state.messages}
                columns={this.chat_message_column}
                onRowClicked={this.chatAction.bind(this)}
              />
              {
                this.state.openChatActions ?
                  <ChatMessageActions
                    message={this.state.message}
                    close={this.closeAction.bind(this)}
                    show={this.state.openChatActions}
                  />
                  : null
              }
            </>
        }
      </Card>
    )
  }

}
