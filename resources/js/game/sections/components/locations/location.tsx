import React, {Fragment, SyntheticEvent} from "react";
import LocationProps from "../../../lib/game/types/map/location-pins/location-props";
import LocationPin from "./location-pin";
import LocationState from "../../../lib/game/types/map/location-pins/location-state";

export default class Location extends React.Component<LocationProps, LocationState> {

    constructor(props: LocationProps) {
        super(props);

        this.state = {
            open_location_modal: false,
            location: null,
        }

    }

    openLocationDetails(locationId: number) {
        if (this.props.locations === null) {
            return;
        }

        let location = this.props.locations.filter((location) => location.id === locationId);

        if (location.length > 0) {
            this.setState({
                open_location_modal: true,
                location: location[0],
            });
        }
    }

    renderLocationPins() {
        if (this.props.locations === null) {
            return;
        }

        return this.props.locations.map((location) => {
            if (location.is_port) {
                return (
                    <LocationPin key={'port-pin-' + location.id}
                                 location={location}
                                 openLocationDetails={this.openLocationDetails.bind(this)}
                                 pin_class={'port-x-pin'}
                    />
                )
            } else {
                return(
                    <LocationPin key={'location-pin-' + location.id}
                                 location={location}
                                 openLocationDetails={this.openLocationDetails.bind(this)}
                                 pin_class={'location-x-pin'}
                    />
                );
            }
        });
    }

    render() {
        return this.renderLocationPins();
    }
}
