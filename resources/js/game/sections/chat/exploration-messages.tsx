import React from "react";
import Messages from "./components/messages";
import ExplorationMessagesComponentProps from "./types/components/exploration-messages-component-props";
import ExplorationMessageType from "./types/deffinitions/exploration-message-type";
import clsx from "clsx";

export default class ExplorationMessages extends React.Component<ExplorationMessagesComponentProps, any> {

    constructor(props: ExplorationMessagesComponentProps) {
        super(props);
    }

    buildMessages() {
        return this.props.exploration_messages.map((message: ExplorationMessageType) => {
            if (message.id !== 0 && message.id !== null) {
                return <li className={clsx('my-2 break-all lg:break-normal ', {
                    'italic': message.make_italic,
                    'text-blue-500 font-bold': message.is_reward,
                    'text-green-300': !message.is_reward

                })} key={message.id}>
                    {message.message}
                </li>
            }

            return <li className={clsx('my-2 break-all lg:break-normal text-green-300', {
                'italic': message.make_italic,
                'text-blue-500 font-bold': message.is_reward,

            })} key={message.id}>
                {message.message}
            </li>
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
