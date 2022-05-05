import React from "react";
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import Messages from "./components/messages";
import Ajax from "../../lib/ajax/ajax";
import ServerMessages from "./server-messages";
import {cloneDeep} from "lodash";
import {AxiosError, AxiosResponse} from "axios";
import {generateServerMessage} from "../../lib/ajax/generate-server-message";
import { DateTime } from "luxon";
import Chat from "./chat";

export default class GameChat extends React.Component<any, any> {

    private tabs: {name: string, key: string, updated: boolean,}[];

    private chat: any;

    private serverMessages: any;

    private privateMessages: any;

    constructor(props: any) {
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

        this.tabs = [{
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
        }];

        // @ts-ignore
        this.chat = Echo.join('chat');

        // @ts-ignore
        this.serverMessages = Echo.private('server-message-' + this.props.user_id);

        // @ts-ignore
        this.privateMessages = Echo.private('private-message-' + this.props.user_id);
    }

    componentDidMount() {

        this.setState({
            is_silenced: this.props.is_silenced,
            can_talk_again_at: this.props.can_talk_again_at,
        })

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
            },() => {
                this.setTabToUpdated('server-messages');
            });
        });

        this.chat.listen('Game.Messages.Events.MessageSentEvent', (event: any) => {
           const chat = cloneDeep(this.state.chat);

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
            }, () => {
                this.setTabToUpdated('chat');
            })
        });
    }

    componentDidUpdate(prevProps: Readonly<any>, prevState: Readonly<any>, snapshot?: any) {
        console.log(this.props.is_silenced, this.state.is_silenced);
        // if (this.props.is_silenced !== this.state.is_silenced && this.props.is_silenced !== null) {
        //     this.setState({
        //         is_silenced: this.props.is_sileneced,
        //         can_talk_again_at: this.props.can_talk_again_at,
        //     })
        // }
    }


    pushSilencedMethod() {
        if (this.state.is_silenced) {
            const chat = cloneDeep(this.state.chat);

            if (chat.length > 1000) {
                chat.length = 500;
            }

            chat.unshift({
                message: 'You are silenced until: ' + DateTime.fromISO(this.state.scan_talk_again_at).toLocal(),
                type: 'error-message',
            })

            this.setState({
                chat: chat
            });
        }
    }

    pushPrivateMessageSent(messageData: string[]) {
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
    }

    resetTabChange(key: string) {
        let tabs = cloneDeep(this.state.tabs);

        tabs = this.state.tabs.map((tab: any) => {
            return  tab.key === key ? {...tab, updated: false} : tab
        });

        this.setState({
            tabs: tabs,
        })
    }

    setTabToUpdated(key: string) {
        let tabs = cloneDeep(this.state.tabs);

        tabs = this.state.tabs.map((tab: any) => {
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
                         set_tab_to_updated={this.setTabToUpdated.bind(this)}
                         push_silenced_messsage={this.pushSilencedMethod.bind(this)}
                         push_private_message_sent={this.pushPrivateMessageSent.bind(this)}
            />
        }

        return (
            <Tabs tabs={this.state.tabs} icon_key={'updated'} when_tab_changes={this.resetTabChange.bind(this)}>
                <TabPanel key={'chat'}>
                    <Chat is_silenced={this.props.is_silenced}
                          chat={this.state.chat}
                          set_tab_to_updated={this.setTabToUpdated.bind(this)}
                          push_silenced_messsage={this.pushSilencedMethod.bind(this)}
                          push_private_message_sent={this.pushPrivateMessageSent.bind(this)}
                    />
                </TabPanel>

                <TabPanel key={'server-messages'}>
                    <ServerMessages server_messages={this.state.server_messages} character_id={this.props.character_id}/>
                </TabPanel>

                <TabPanel key={'exploration-messages'}>

                </TabPanel>
            </Tabs>
        )
    }
}
