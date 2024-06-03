import React from "react";
import LocationPinProps from "../../map/types/map/location-pins/location-pin-props";

export default class LocationPin extends React.Component<LocationPinProps, {}> {
    constructor(props: LocationPinProps) {
        super(props);
    }

    openLocationInformation(event: any) {
        this.props.openLocationDetails(
            parseInt(event.target.getAttribute("data-location-id")),
        );
    }

    render() {
        return (
            <button
                key={this.props.location.id}
                data-location-id={this.props.location.id}
                className={this.props.pin_class}
                style={{
                    top: this.props.location.y,
                    left: this.props.location.x,
                }}
                onClick={this.openLocationInformation.bind(this)}
                onMouseEnter={this.props.onMouseEnter}
            ></button>
        );
    }
}
