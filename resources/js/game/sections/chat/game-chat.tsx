import React from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";
import PopOverContainer from "../../components/ui/popover/pop-over-container";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import Messages from "./components/messages";
import Ajax from "../../lib/ajax/ajax";
import ServerMessages from "./server-messages";
import {cloneDeep} from "lodash";
import {AxiosError, AxiosResponse} from "axios";
import {generateServerMessage} from "../../lib/ajax/generate-server-message";

export default class GameChat extends React.Component<any, any> {

    private tabs: {name: string, key: string}[];

    private chat: any;

    private serverMessages: any;

    private privateMessages: any;

    constructor(props: any) {
        super(props);

        this.state = {
            chat: [],
            server_messages: [],
            message: '',
        }

        this.tabs = [{
            key: 'chat',
            name: 'Chat'
        }, {
            key: 'server-messages',
            name: 'Server Message',
        }, {
            key: 'exploration-messages',
            name: 'Exploration'
        }];

        // @ts-ignore
        this.chat = Echo.join('chat');

        // @ts-ignore
        this.serverMessages = Echo.private('server-message-' + this.props.user_id);

        // @ts-ignore
        this.privateMessages = Echo.private('private-message-' + this.props.user_id);
    }

    componentDidMount() {
        // @ts-ignore
        this.serverMessages.listen('Game.Messages.Events.ServerMessageEvent', (event: any) => {
            let messages = cloneDeep(this.state.server_messages);

            if (messages.length > 1000) {
                messages.length = 250; // Remove the last 3/4's worth of messages
            }

            messages.unshift({
                id: event.id + '-' + (Math.random() + 1).toString(36).substring(7),
                message: event.message,
                event_id: event.id,
            });

            this.setState({
                server_messages: messages
            });
        });

        this.chat.listen('Game.Messages.Events.MessageSentEvent', (event: any) => {
           const chat = cloneDeep(this.state.chat);

           if (chat.length > 1000) {
               chat.length = 500;
           }

           chat.unshift({
               color: event.message.color,
               map_name: event.message.map_name,
               character_name: event.name,
               message: event.message.message,
               x: event.message.x_position,
               y: event.message.y_position,
               type: 'chat',
           });

           this.setState({
               chat: chat,
           })
        });

        this.privateMessages.listen('Game.Messages.Events.PrivateMessageEvent', (event: any) => {
            const chat = cloneDeep(this.state.chat);

            if (chat.length > 1000) {
                chat.length = 500;
            }

            chat.unshift({
                message: event.message,
                type: 'private-message-received',
                from: event.from,
            });

            this.setState({
                chat: chat,
            })
        });
    }

    setMessage(e: React.ChangeEvent<HTMLInputElement>) {
        this.setState({
            message: e.target.value,
        });
    }

    sendMessage(e?: any) {

        if (typeof e !== 'undefined') {
            if (e.key === 'Enter') {
                this.handleMessage();
            }
        } else {
            this.handleMessage();
        }
    }

    privateMessage(characterName: string) {
        this.setState({
            message: '/m ' + characterName + ': '
        });
    }

    handleMessage() {
        if (this.state.message.includes('/m')) {
            this.sendPrivateMessage();
        } else {
            this.sendPublicMessage();
        }
    }

    sendPublicMessage() {
        this.setState({
            message: '',
        });

        (new Ajax()).setRoute('public-message').setParameters({
            message: this.state.message,
        }).doAjaxCall('post', (result: AxiosResponse) => {
        }, (error: AxiosError) => {

        });
    }

    sendPrivateMessage() {
        const messageData = this.state.message.match(/^\/m\s+(\w+[\w| ]*):\s*(.*)/);

        if (messageData === null) {
            return generateServerMessage('invalid_command');
        }

        const chat = cloneDeep(this.state.chat);

        if (chat.length >= 1000) {
            chat.length = 500;
        }

        chat.unshift({
            message: 'Sent to ' + messageData[1] + ': ' + messageData[2],
            type: 'private-message-sent',
        })

        this.setState({
            message: '',
            chat: chat,
        });

        (new Ajax()).setRoute('private-message').setParameters({
            user_name: messageData[1],
            message: messageData[2],
        }).doAjaxCall('post', (result: AxiosResponse) => {}, (error: AxiosError) => {});
    }

    renderChatMessages() {
        const self = this;

        return this.state.chat.map(function(message: any) {
            switch (message.type) {
                case 'chat':
                    return <li style={{color: message.color}} className='mb-2'>
                        [{message.map_name} {message.x}/{message.y}] <button type='button' className='underline' onClick={() => self.privateMessage(message.character_name)}>{message.character_name}</button>: {message.message}
                    </li>
                case 'private-message-sent':
                    return <li className='text-fuchsia-400 italic mb-2'>{message.message}</li>
                case 'private-message-received':
                    return <li className='text-fuchsia-300 italic mb-2'><button type='button' className='underline' onClick={() => self.privateMessage(message.from)}>{message.from}</button>: {message.message}</li>
                default:
                    return null;

            }
        });
    }

    render() {
        return (
            <Tabs tabs={this.tabs}>
                <TabPanel key={'chat'}>
                    <div className='flex items-center mb-4'>
                        <div className='grow pr-4'>
                            <input type='text' name='chat' className='form-control' onChange={this.setMessage.bind(this)} onKeyDown={this.sendMessage.bind(this)} value={this.state.message} />
                        </div>
                        <div className='flex-none'>
                            <PrimaryButton button_label={'Send'} on_click={this.sendMessage.bind(this)} />
                        </div>
                    </div>
                    <div>
                        <Messages>
                            {this.renderChatMessages()}
                        </Messages>
                    </div>
                </TabPanel>

                <TabPanel key={'server-messages'}>
                    <ServerMessages server_messages={this.state.server_messages} character_id={this.props.character_id} />
                </TabPanel>

                <TabPanel key={'exploration-messages'}>

                </TabPanel>
            </Tabs>
        )
    }
}
