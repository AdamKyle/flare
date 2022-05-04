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

export default class GameChat extends React.Component<any, any> {

    private tabs: {name: string, key: string}[];

    private serverMessages: any;

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
        this.serverMessages = Echo.private('server-message-' + this.props.user_id);
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

            console.log(messages);

            this.setState({
                server_messages: messages
            });
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
                (new Ajax()).setRoute('public-message').setParameters({
                    message: this.state.message,
                }).doAjaxCall('post', (result: AxiosResponse) => {
                    console.log(result.data);
                }, (error: AxiosError) => {

                });
            }
        } else {
            (new Ajax()).setRoute('public-message').setParameters({
                message: this.state.message,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                console.log(result.data);
            }, (error: AxiosError) => {

            });
        }
    }

    render() {
        return (
            <Tabs tabs={this.tabs}>
                <TabPanel key={'chat'}>
                    <div className='flex items-center mb-4'>
                        <div className='grow pr-4'>
                            <input type='text' name='chat' className='form-control' onChange={this.setMessage.bind(this)} onKeyDown={this.sendMessage.bind(this)}/>
                        </div>
                        <div className='flex-none'>
                            <PrimaryButton button_label={'Send'} on_click={this.sendMessage.bind(this)} />
                        </div>
                    </div>
                    <div>
                        <Messages>
                            <li>[SUR X/Y] Character Name: Oh hello there ...</li>
                            <li>[SUR X/Y] Character Name: Oh hello there ...</li>
                            <li>[SUR X/Y] Character Name: Oh hello there ...</li>
                            <li>[SUR X/Y] Character Name: Oh hello there ...</li>
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
