import React, {Fragment} from "react";
import Messages from "./components/messages";
import {cloneDeep} from "lodash";
import ItemComparison from "./modals/item-comparison";

export default class ServerMessages extends React.Component<any, any> {

    private serverMessages: any;

    constructor(props: any) {
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
        return this.props.server_messages.map((message: { message: string, id: number, slot_id: number | null, event_id: number | 0 }) => {
            if (message.event_id !== 0 && message.event_id !== null) {
                return <li className='text-pink-400 my-2' key={message.id}>
                    <button type='button' className='italic underline hover:text-pink-300' onClick={() => this.viewItem(message.event_id)}>{message.message} <i className='ra ra-anvil'></i></button>
                </li>
            }

            return <li className='text-pink-400 my-2' key={message.id}>{message.message}</li>
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
