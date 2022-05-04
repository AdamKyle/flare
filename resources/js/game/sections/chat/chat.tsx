import React, {Fragment} from "react";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import Messages from "./components/messages";
import {cloneDeep} from "lodash";
import {DateTime} from "luxon";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {generateServerMessage} from "../../lib/ajax/generate-server-message";

export default class Chat extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            message: '',
        }
    }

    setMessage(e: React.ChangeEvent<HTMLInputElement>) {
        this.setState({
            message: e.target.value,
        });
    }

    sendMessage(e?: any) {

        if (this.props.is_silenced) {
            return this.props.push_silenced_messsage();
        }

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
        if (this.state.message === '') {
            return generateServerMessage('invalid_command');
        }

        if (this.props.is_silenced) {
            return this.props.push_silenced_messsage()
        }

        this.setState({
            message: '',
        });

        (new Ajax()).setRoute('public-message').setParameters({
            message: this.state.message,
        }).doAjaxCall('post', (result: AxiosResponse) => {
        }, (error: AxiosError) => {
            this.handleMessageErrors(error);
        });
    }

    sendPrivateMessage() {
        const messageData = this.state.message.match(/^\/m\s+(\w+[\w| ]*):\s*(.*)/);

        if (messageData === null) {
            return generateServerMessage('invalid_command');
        }

        if (this.props.is_silenced) {
            return this.props.push_silenced_messsage();
        }

        this.props.push_private_message_sent(messageData);

        this.setState({
            message: '',
        });

        (new Ajax()).setRoute('private-message').setParameters({
            user_name: messageData[1],
            message: messageData[2],
        }).doAjaxCall('post', (result: AxiosResponse) => {}, (error: AxiosError) => {
            this.handleMessageErrors(error);
        });
    }

    handleMessageErrors(error: AxiosError) {
        let response: AxiosResponse | undefined = undefined;

        if (error.hasOwnProperty('response')) {
            response = error.response;
        }

        if (response?.status === 429) {
            generateServerMessage('chatting_to_much')
        }

        this.props.set_tab_to_updated('server-messages');
    }

    renderChatMessages() {
        const self = this;

        return this.props.chat.map(function(message: any) {
            switch (message.type) {
                case 'chat':
                    return <li style={{color: message.color}} className='mb-2'>
                        [{message.map_name} {message.x}/{message.y}] <button type='button' className='underline' onClick={() => self.privateMessage(message.character_name)}>{message.character_name}</button>: {message.message}
                    </li>
                case 'private-message-sent':
                    return <li className='text-fuchsia-400 italic mb-2'>{message.message}</li>
                case 'private-message-received':
                    return <li className='text-fuchsia-300 italic mb-2'><button type='button' className='underline' onClick={() => self.privateMessage(message.from)}>{message.from}</button>: {message.message}</li>
                case 'error-message':
                    return <li className='text-red-400 bold mb-2'>{message.message}</li>
                default:
                    return null;

            }
        });
    }

    render() {
        return(
            <Fragment>
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
            </Fragment>
        )
    }
}
