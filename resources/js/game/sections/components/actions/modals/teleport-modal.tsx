import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import TeleportModalProps from "../../../../lib/game/types/map/modals/teleport-modal-props";


export default class TeleportModal extends React.Component<TeleportModalProps, any> {

    constructor(props: TeleportModalProps) {
        super(props);
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open} handle_close={this.props.handle_close} title={this.props.title}>

            </Dialogue>
        )
    }
}
