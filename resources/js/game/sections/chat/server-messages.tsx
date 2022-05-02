import React from "react";
import Messages from "./components/messages";
import {cloneDeep} from "lodash";

export default class ServerMessages extends React.Component<any, any> {

    private serverMessages: any;

    constructor(props: any) {
        super(props);
    }

    viewItem(itemId: number) {
        console.log(itemId);
    }

    buildMessages() {
        return this.props.server_messages.map((message: { message: string, id: number, slot_id: number | null, event_id: number | 0 }) => {
            if (message.event_id !== 0 && message.event_id !== null) {
                return <li className='text-pink-400 my-2' key={message.id}>
                    <button type='button' className='italic underline hover:text-pink-300' onClick={() => this.viewItem(message.event_id)}>{message.message}</button>
                </li>
            }

            return <li className='text-pink-400 my-2' key={message.id}>{message.message}</li>
        });
    }

    render() {
        return (
            <Messages>
                {this.buildMessages()}
            </Messages>
        )
    }
}
