import React from "react";
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";
import Ajax from "../../lib/ajax/ajax";
import ServerMessages from "./server-messages";
import {AxiosError, AxiosResponse} from "axios";
import Chat from "./chat";
import GameChatProps from "../../lib/game/chat/game-chat-props";
import GameChatState from "../../lib/game/chat/game-chat-state";

export default class GameChat extends React.Component<GameChatProps, GameChatState> {
    private chat: any;

    private serverMessages: any;

    private privateMessages: any;

    private globalMessage: any;

    constructor(props: GameChatProps) {
        super(props);

        this.state = {
            chat: [],
            server_messages: [],
            message: '',
            is_silenced: false,
            can_talk_again_at: null,
            tabs: [{
                key: 'chat',
                name: 'Chat',
                updated: false,
            }, {
                key: 'server-messages',
                name: 'Server Message',
                updated: false,
            }, {
                key: 'exploration-messages',
                name: 'Exploration',
                updated: false,
            }]
        }

        // @ts-ignore
        this.chat = Echo.join('chat');

        // @ts-ignore
        this.serverMessages = Echo.private('server-message-' + this.props.user_id);

        // @ts-ignore
        this.privateMessages = Echo.private('private-message-' + this.props.user_id);

        // @ts-ignore
        this.globalMessage = Echo.join('global-message');
    }

    componentDidMount() {

        this.setState({
            is_silenced: this.props.is_silenced,
            can_talk_again_at: this.props.can_talk_again_at,
        });

        (new Ajax()).setRoute('last-chats').doAjaxCall('get', (result: AxiosResponse) => {
            const chats = result.data.map((chat: any) => {
                if (chat.name === 'Admin') {
                    return {
                        message: chat.message,
                        character_name: 'The Creator',
                        type: 'creator-message',
                    }
                } else {
                    return {
                        color: chat.color,
                        map_name: chat.map,
                        character_name: chat.name,
                        message: chat.message,
                        x: chat.x_position,
                        y: chat.y_position,
                        type: 'chat',
                    }
                }
            }).filter((chat: any) => typeof chat !== 'undefined');

            this.setState({
                chat: [...this.state.chat, ...chats],
            });
        }, (error: AxiosError) => {

        })

        // @ts-ignore
        this.serverMessages.listen('Game.Messages.Events.ServerMessageEvent', (event: any) => {
            let messages = JSON.parse(JSON.stringify(this.state.server_messages));

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
            },() => {
                this.setTabToUpdated('server-messages');
            });
        });

        this.chat.listen('Game.Messages.Events.MessageSentEvent', (event: any) => {
           const chat = JSON.parse(JSON.stringify(this.state.chat))

           if (chat.length > 1000) {
               chat.length = 500;
           }

           console.log(event);

           if (event.name === 'Admin') {
               chat.unshift({
                   message: event.message.message,
                   character_name: 'The Creator',
                   type: 'creator-message',
               });
           } else {
               chat.unshift({
                   color: event.message.color,
                   map_name: event.message.map_name,
                   character_name: event.name,
                   message: event.message.message,
                   x: event.message.x_position,
                   y: event.message.y_position,
                   type: 'chat',
               });
           }

           this.setState({
               chat: chat,
           }, () => {
               this.setTabToUpdated('chat');
           })
        });

        this.privateMessages.listen('Game.Messages.Events.PrivateMessageEvent', (event: any) => {
            const chat = JSON.parse(JSON.stringify(this.state.chat))

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
            }, () => {
                this.setTabToUpdated('chat');
            })
        });

        this.globalMessage.listen('Game.Messages.Events.GlobalMessageEvent', (event: any) => {
            const chat = JSON.parse(JSON.stringify(this.state.chat))

            if (chat.length > 1000) {
                chat.length = 500;
            }

            chat.unshift({
                message: event.message,
                type: 'global-message',
            });

            this.setState({
                chat: chat,
            }, () => {
                this.setTabToUpdated('chat');
            })
        })
    }

    componentDidUpdate(prevProps: Readonly<any>, prevState: Readonly<any>, snapshot?: any) {

        if (this.props.is_silenced === null) {
            return;
        }

        if (this.props.is_silenced !== this.state.is_silenced) {
            this.setState({
                is_silenced: this.props.is_silenced,
                can_talk_again_at: this.props.can_talk_again_at,
            });
        }
    }


    pushSilencedMethod() {
        if (this.state.is_silenced) {
            const chat = JSON.parse(JSON.stringify(this.state.chat))

            if (chat.length > 1000) {
                chat.length = 500;
            }

            chat.unshift({
                message: 'You child, have been chatting up a storm. Slow down. I\'ll let you know whe you can talk again ...',
                type: 'error-message',
            })

            this.setState({
                chat: chat
            });
        }
    }

    pushPrivateMessageSent(messageData: string[]) {
        const chat = JSON.parse(JSON.stringify(this.state.chat))

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
    }

    pushErrorMessage(message: string) {
        const chat = JSON.parse(JSON.stringify(this.state.chat))

        if (chat.length > 1000) {
            chat.length = 500;
        }

        chat.unshift({
            message: message,
            type: 'error-message',
        })

        this.setState({
            chat: chat
        });
    }

    resetTabChange(key: string) {
        const tabs = this.state.tabs.map((tab: any) => {
            return  tab.key === key ? {...tab, updated: false} : tab
        });

        this.setState({
            tabs: tabs,
        })
    }

    setTabToUpdated(key: string) {
        const tabs = this.state.tabs.map((tab: any) => {
            return  tab.key === key ? {...tab, updated: true} : tab
        });

        this.setState({
            tabs: tabs,
        })
    }

    render() {
        if (this.props.is_admin) {
            return <Chat is_silenced={this.props.is_silenced}
                         chat={this.state.chat}
                         can_talk_again_at={this.props.can_talk_again_at}
                         set_tab_to_updated={this.setTabToUpdated.bind(this)}
                         push_silenced_message={this.pushSilencedMethod.bind(this)}
                         push_private_message_sent={this.pushPrivateMessageSent.bind(this)}
                         push_error_message={this.pushErrorMessage.bind(this)}
            />
        }

        return (
            <div className='mt-4 mb-4'>
                <Tabs tabs={this.state.tabs} icon_key={'updated'} when_tab_changes={this.resetTabChange.bind(this)}>
                    <TabPanel key={'chat'}>
                        <Chat is_silenced={this.props.is_silenced}
                              can_talk_again_at={this.props.can_talk_again_at}
                              chat={this.state.chat}
                              set_tab_to_updated={this.setTabToUpdated.bind(this)}
                              push_silenced_message={this.pushSilencedMethod.bind(this)}
                              push_private_message_sent={this.pushPrivateMessageSent.bind(this)}
                              push_error_message={this.pushErrorMessage.bind(this)}
                        />
                    </TabPanel>

                    <TabPanel key={'server-messages'}>
                        <ServerMessages server_messages={this.state.server_messages} character_id={this.props.character_id} view_port={this.props.view_port}/>
                    </TabPanel>

                    <TabPanel key={'exploration-messages'}>

                    </TabPanel>
                </Tabs>
            </div>
        )
    }
}
