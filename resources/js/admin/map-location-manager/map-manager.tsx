import React, { Component } from "react";
import GridOverlay from "./grid-overlay";
import Ajax from "../../game/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../game/components/ui/progress-bars/loading-progress-bar";
import MapManagerProps from "./types/map-manager-props";
import MapManagerState from "./types/map-manager-state";

export default class MapManager extends Component<
    MapManagerProps,
    MapManagerState
> {
    constructor(props: MapManagerProps) {
        super(props);

        this.state = {
            loading: true,
            imgSrc: null,
            coordinates: { x: [], y: [] },
            locations: [],
        };
    }

    componentDidMount() {
        new Ajax().setRoute("admin/map-manager/" + this.props.mapId).doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                const coordinates = {
                    x: result.data.x_coordinates,
                    y: result.data.y_coordinates,
                };

                this.setState({
                    loading: false,
                    imgSrc: result.data.path,
                    coordinates: coordinates,
                    locations: result.data.locations,
                });
            },
            (error: AxiosError) => {},
        );
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
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
