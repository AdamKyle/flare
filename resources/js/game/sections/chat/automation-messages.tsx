import React from "react";
import clsx from "clsx";
import Messages from "./components/messages";
import AutomationMessagesComponentProps from "./types/components/automation-messages-component-props";
import ExplorationMessageType from "./types/deffinitions/automation-message-type";

export default class AutomationMessages extends React.Component<
    AutomationMessagesComponentProps,
    any
> {
    constructor(props: AutomationMessagesComponentProps) {
        super(props);
    }

    buildMessages() {
        return this.props.automation_messages.map(
            (message: ExplorationMessageType) => {
                if (message.id !== 0 && message.id !== null) {
                    return (
                        <li
                            className={clsx(
                                "my-2 break-word lg:break-normal ",
                                {
                                    italic: message.make_italic,
                                    "text-blue-500 font-bold":
                                        message.is_reward,
                                    "text-green-300": !message.is_reward,
                                },
                            )}
                            key={message.id}
                        >
                            [{message.time_stamp}] {message.message}
                        </li>
                    );
                }

                return (
                    <li
                        className={clsx(
                            "my-2 break-word lg:break-normal text-green-300",
                            {
                                italic: message.make_italic,
                                "text-blue-500 font-bold": message.is_reward,
                            },
                        )}
                        key={message.id}
                    >
                        [{message.time_stamp}] {message.message}
                    </li>
                );
            },
        );
    }

    render() {
        return <Messages>{this.buildMessages()}</Messages>;
    }
}
