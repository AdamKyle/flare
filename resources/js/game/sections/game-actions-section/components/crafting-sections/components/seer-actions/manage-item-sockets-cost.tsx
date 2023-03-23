import React from "react";
import {formatNumber} from "../../../../../../lib/game/format-number";

export default class ManageItemSocketsCost extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className='mt-4 mb-2'>
                <dl>
                    <dt>Gold Bar Cost:</dt>
                    <dd>{formatNumber(this.props.socket_cost)}</dd>
                    <dt>Items Socket Amount:</dt>
                    <dd>{this.props.get_item_info('socket_amount')}</dd>
                </dl>
            </div>
        )
    }
}
