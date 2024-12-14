import React, { Component } from "react";
import LocationDetails from "../../game/sections/map/types/location-details";
import InitializeMapAjax from "./ajax/initialize-map-ajax";
import { gridOverLayContainer } from "./container/grid-overlay-container";
import GridOverlay from "./grid-overlay";
import MapManagerProps from "./types/map-manager-props";
import MapManagerState from "./types/map-manager-state";
import NpcDetails from "./types/deffinitions/npc-details";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../components/ui/alerts/simple-alerts/danger-alert";

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
            npcs: [],
            error_message: null,
        };

        this.initializeMap = gridOverLayContainer().fetch(InitializeMapAjax);
    }

    componentDidMount() {
        this.initializeMap.initializeMap(this, this.props.mapId);
    }

    updateLocationsAndNpcs(
        locations: LocationDetails[] | [],
        npcs: NpcDetails[] | [],
    ) {
        this.setState({
            locations: locations,
            npcs: npcs,
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
                    map_id={this.props.mapId}
                    locations={this.state.locations}
                    npcs={this.state.npcs}
                    updateLocationsAndNpcs={this.updateLocationsAndNpcs.bind(
                        this,
                    )}
                />
            </div>
        );
    }
}
