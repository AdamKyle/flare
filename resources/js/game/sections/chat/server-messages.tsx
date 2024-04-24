import React, { Fragment } from "react";
import Messages from "./components/messages";
import ItemComparison from "./modals/item-comparison";
import ServerMessagesComponentProps from "./types/components/server-messages-component-props";
import ServerMessagesComponentState from "./types/components/server-messages-component-state";
import { viewPortWatcher } from "../../lib/view-port-watcher";
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
        };
    }

    viewItem(slotId?: number, isQuest?: any) {
        this.setState({
            slot_id: typeof slotId !== "undefined" ? slotId : 0,
            view_item: !this.state.view_item,
            is_quest_item: typeof isQuest !== "undefined" ? isQuest : false,
        });
    }

    buildMessages() {
        return this.props.server_messages.map((message: any) => {
            if (message.event_id !== 0 && message.event_id !== null) {
                return (
                    <li
                        className="text-pink-400 my-2 break-word lg:break-normal"
                        key={message.id}
                    >
                        <button
                            type="button"
                            className="italic underline hover:text-pink-300"
                            onClick={() =>
                                this.viewItem(
                                    message.event_id,
                                    message.is_quest_item,
                                )
                            }
                        >
                            {message.message} <i className="ra ra-anvil"></i>
                        </button>
                    </li>
                );
            }

            return (
                <li
                    className="text-pink-400 my-2 break-word lg:break-normal"
                    key={message.id}
                >
                    {message.message}
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
                        is_automation_running={this.props.is_automation_running}
                    />
                ) : null}
            </Fragment>
        );
    }
}
