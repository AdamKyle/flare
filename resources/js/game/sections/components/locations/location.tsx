import React, {Fragment, SyntheticEvent} from "react";
import LocationProps from "../../../lib/game/types/map/location-pins/location-props";
import LocationPin from "./location-pin";
import LocationState from "../../../lib/game/types/map/location-pins/location-state";
import LocationModal from "./modals/location-modal";
import {viewPortWatcher} from "../../../lib/view-port-watcher";

export default class Location extends React.Component<LocationProps, LocationState> {

    constructor(props: LocationProps) {
        super(props);

        this.state = {
            open_location_modal: false,
            location: null,
            view_port: null,
        }
    }

    componentDidMount() {
        viewPortWatcher(this);
    }

    componentDidUpdate() {
        if (this.state.view_port !== null) {
            if (this.state.view_port < 600 && this.state.open_location_modal) {
                this.setState({
                    location: null,
                    open_location_modal: false,
                });
            }
        }
    }

    closeLocationDetails() {
        this.setState({
            open_location_modal: false,
            location: null,
        });
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
        return (
            <Fragment>
                {this.renderLocationPins()}
                {
                    this.state.open_location_modal && typeof this.state.location !== 'undefined' && this.state.location !== null ?
                        <LocationModal is_open={this.state.open_location_modal}
                                       handle_close={this.closeLocationDetails.bind(this)}
                                       title={this.state.location.name}
                                       location={this.state.location}
                                       character_position={this.props.character_position}
                                       currencies={this.props.currencies}
                                       teleport_player={this.props.teleport_player}
                                       hide_secondary_button={false}
                                       can_move={this.props.can_move}
                                       is_automation_running={this.props.is_automation_running}
                                       is_dead={this.props.is_dead}
                        />
                    : null
                }

            </Fragment>
        );
    }
}
