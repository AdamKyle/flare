import React from "react";
import ViewLocationModalProps from "../types/view-location-modal-props";
import LocationDetails from "./components/view-details/location-details";

export default class ViewLocationModal extends React.Component<
    ViewLocationModalProps,
    any
> {
    constructor(props: ViewLocationModalProps) {
        super(props);
    }

    renderModal() {
        if (this.props.location !== null) {
            return (
                <LocationDetails
                    location={this.props.location}
                    handle_close={this.props.handle_close}
                />
            );
        }

        return null;
    }

    render() {
        return this.renderModal();
    }
}
