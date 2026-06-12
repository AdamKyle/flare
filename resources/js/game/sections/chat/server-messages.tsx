import React, { Fragment } from "react";
import Messages from "./components/messages";
import ServerMessagesComponentProps from "./types/components/server-messages-component-props";
import ServerMessagesComponentState from "./types/components/server-messages-component-state";
import ItemDetailsModal from "../../components/modals/item-details/item-details-modal";

export default class ServerMessages extends React.Component<
    ServerMessagesComponentProps,
    ServerMessagesComponentState
> {
    constructor(props: ServerMessagesComponentProps) {
        super(props);

        this.state = {
            slot_id: 0,
            view_item: false,
            is_quest_item: false,
            source: null,
        };
    }

    viewItem(slotId?: number, isQuest?: any, source?: string | null) {
        this.setState({
            slot_id: typeof slotId !== "undefined" ? slotId : 0,
            view_item: !this.state.view_item,
            is_quest_item: typeof isQuest !== "undefined" ? isQuest : false,
            source: typeof source !== "undefined" ? source : null,
        });
    }

    buildMessages() {
        return this.props.server_messages.map((message: any) => {
            if (message.event_id !== 0 && message.event_id !== null) {
                const linkText = message.link_text ?? message.message;
                const linkIndex = message.message.indexOf(linkText);
                const messagePrefix =
                    linkIndex >= 0
                        ? message.message.substring(0, linkIndex)
                        : "";
                const messageSuffix =
                    linkIndex >= 0
                        ? message.message.substring(linkIndex + linkText.length)
                        : "";

                return (
                    <li
                        className="text-pink-400 my-2 break-word lg:break-normal"
                        key={message.id}
                    >
                        <span>[{message.time_stamp}] </span>
                        {messagePrefix}
                        <button
                            type="button"
                            className="italic underline text-pink-500 dark:text-pink-300 hover:text-pink-300"
                            onClick={() =>
                                this.viewItem(
                                    message.event_id,
                                    message.is_quest_item,
                                    message.source,
                                )
                            }
                        >
                            {linkText} <i className="ra ra-anvil"></i>
                        </button>
                        {messageSuffix}
                    </li>
                );
            }

            return (
                <li
                    className="text-pink-400 my-2 break-word lg:break-normal"
                    key={message.id}
                >
                    [{message.time_stamp}] {message.message}
                </li>
            );
        });
    }

    render() {
        return (
            <Fragment>
                <Messages>{this.buildMessages()}</Messages>

                {this.state.view_item && this.state.slot_id !== 0 ? (
                    <ItemDetailsModal
                        is_dead={false}
                        is_open={this.state.view_item}
                        manage_modal={this.viewItem.bind(this)}
                        character_id={this.props.character_id}
                        slot_id={this.state.slot_id}
                        source={this.state.source}
                        is_automation_running={this.props.is_automation_running}
                    />
                ) : null}
            </Fragment>
        );
    }
}
