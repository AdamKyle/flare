import React, { Component } from "react";
import GridOverlay from "./grid-overlay";
import Ajax from "../../game/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../game/components/ui/progress-bars/loading-progress-bar";
import MapManagerProps from "./types/map-manager-props";
import MapManagerState from "./types/map-manager-state";
import DangerAlert from "../../game/components/ui/alerts/simple-alerts/danger-alert";
import InitializeMapAjax from "./ajax/initialize-map-ajax";
import {gridOverLayContainer} from "./container/grid-overlay-container";

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

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        if (this.state.error_message !== null) {
            return <DangerAlert>
                {this.state.error_message}
            </DangerAlert>
        }

        return (
            <div>
                <GridOverlay
                    coordinates={this.state.coordinates}
                    mapSrc={this.state.imgSrc}
                    locations={this.state.locations}
                />
            </div>
        );
    }
}
