import React, { Component } from "react";
import DangerAlert from "../../game/components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../game/components/ui/progress-bars/loading-progress-bar";
import LocationDetails from "../../game/sections/map/types/location-details";
import InitializeMapAjax from "./ajax/initialize-map-ajax";
import { gridOverLayContainer } from "./container/grid-overlay-container";
import GridOverlay from "./grid-overlay";
import MapManagerProps from "./types/map-manager-props";
import MapManagerState from "./types/map-manager-state";

export default class MapManager extends Component<
    MapManagerProps,
    MapManagerState
> {
    private initializeMap: InitializeMapAjax;

    constructor(props: MapManagerProps) {
        super(props);

        this.state = {
            loading: true,
            imgSrc: null,
            coordinates: { x: [], y: [] },
            locations: [],
            error_message: null,
        };

        this.initializeMap = gridOverLayContainer().fetch(InitializeMapAjax);
    }

    componentDidMount() {
        this.initializeMap.initializeMap(this, this.props.mapId);
    }

    updateLocations(locations: LocationDetails[] | []) {
        this.setState({
            locations: locations,
        });
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        if (this.state.error_message !== null) {
            return <DangerAlert>{this.state.error_message}</DangerAlert>;
        }

        return (
            <div>
                <GridOverlay
                    coordinates={this.state.coordinates}
                    mapSrc={this.state.imgSrc}
                    locations={this.state.locations}
                    updateLocations={this.updateLocations.bind(this)}
                />
            </div>
        );
    }
}
