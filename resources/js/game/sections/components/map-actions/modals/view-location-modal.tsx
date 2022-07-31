import React from "react";
import ViewLocationModalProps from "../../../../lib/game/map/types/view-location-modal-props";

export default class ViewLocationModal extends React.Component<ViewLocationModalProps, any> {

    constructor(props: ViewLocationModalProps) {
        super(props);
    }

    renderModal() {
        if (this.props.location !== null) {
            return null;
        }

        return null;
    }

    render() {
        return this.renderModal();
    }
}
