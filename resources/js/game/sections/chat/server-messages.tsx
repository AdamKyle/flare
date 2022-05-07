import React, {Fragment} from "react";
import Messages from "./components/messages";
import {cloneDeep} from "lodash";
import ItemComparison from "./modals/item-comparison";
import ServerMessagesComponentProps from "../../lib/game/chat/components/server-messages-component-props";
import ServerMessagesComponentState from "../../lib/game/chat/components/server-messages-component-state";

export default class ServerMessages extends React.Component<ServerMessagesComponentProps, ServerMessagesComponentState> {

    constructor(props: ServerMessagesComponentProps) {
        super(props);

        this.state = {
            slot_id: 0,
            view_item: false,
        }
    }

    viewItem(slotId?: number) {
        this.setState({
            slot_id: typeof slotId !== 'undefined' ? slotId : 0,
            view_item: !this.state.view_item
        })
    }

    buildMessages() {
        return this.props.server_messages.map((message) => {
            if (message.event_id !== 0 && message.event_id !== null) {
                return <li className='text-pink-400 my-2 break-all lg:break-normal' key={message.id}>
                    <button type='button' className='italic underline hover:text-pink-300' onClick={() => this.viewItem(message.event_id)}>{message.message} <i className='ra ra-anvil'></i></button>
                </li>
            }

            return <li className='text-pink-400 my-2 break-all lg:break-normal' key={message.id}>{message.message}</li>
        });
    }

    render() {
        return (
            <Fragment>
                <Messages>
                    {this.buildMessages()}
                </Messages>

                {
                    this.state.view_item && this.state.slot_id !== 0 ?
                        <ItemComparison
                            is_open={this.state.view_item}
                            manage_modal={this.viewItem.bind(this)}
                            character_id={this.props.character_id}
                            slot_id={this.state.slot_id}
                        />
                    : null
                }
            </Fragment>
        )
    }
}
