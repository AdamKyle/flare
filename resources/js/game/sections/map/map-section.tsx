import React, {Fragment} from "react";
import {AxiosError, AxiosResponse} from "axios";
import Draggable from 'react-draggable';
import {dragMap} from "../../lib/game/map/map-position";
import MapState from "../../lib/game/types/map/map-state";
import MapProps from '../../lib/game/types/map/map-props';
import Ajax from "../../lib/ajax/ajax";
import Location from "../components/locations/location";
import MapActions from "../components/actions/map/map-actions";

export default class MapSection extends React.Component<MapProps, MapState> {

    constructor(props: MapProps) {
        super(props);

        this.state = {
            map_url: '',
            map_position: {
                x: 0, y: 0
            },
            character_position: {
                x: 0, y: 0
            },
            bottom_bounds: 0,
            right_bounds: 0,
            locations: null,
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('map/' + this.props.characterId)
                    .doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                map_url: result.data.map_url,
                locations: result.data.locations,
                character_position: {
                    x: result.data.character_map.character_position_x,
                    y: result.data.character_map.character_position_y,
                },
            });
        }, (err: AxiosError) => {

        });
    }

    fetchLeftBounds(): number {
        if (this.props.view_port < 600) {
            return -210;
        }

        if (this.props.view_port < 990) {
            return -110;
        }

        if (this.props.view_port < 1024) {
            return 0;
        }

        return -110
    }

    handleDrag(e: any, position: {x: number, y: number}) {
        this.setState(dragMap(
            position, this.state.bottom_bounds, this.state.right_bounds
        ));
    }

    playerIcon(): {top: string, left: string} {
        return {
            top: this.state.character_position.y + 'px',
            left: this.state.character_position.x + 'px',
        }
    }

    getStyle(): { backgroundImage: string, height: number, width?: number } {
        if (this.props.view_port > 770 && this.props.view_port < 1600) {
            return {backgroundImage: `url("${this.state.map_url}")`, height: 500};
        }

        return {backgroundImage: `url("${this.state.map_url}")`, height: 500, width: 500};
    }

    render() {

        if (this.state.loading) {
            return (
                <Fragment>
                    <p>One Moment ... </p>
                </Fragment>
            );
        }

        return(
            <Fragment>
                <div className='overflow-hidden max-h-[350px]'>
                    <Draggable
                        position={this.state.map_position}
                        bounds={{top: -160, left: this.fetchLeftBounds(), right: this.state.right_bounds, bottom: this.state.bottom_bounds}}
                        handle=".handle"
                        defaultPosition={{x: 0, y: 0}}
                        grid={[16, 16]}
                        scale={1}
                        onDrag={this.handleDrag.bind(this)}
                    >
                        <div>
                            <div className='handle game-map'
                                 style={this.getStyle()}>

                                <Location locations={this.state.locations}/>

                                <div className="map-x-pin" style={this.playerIcon()}></div>
                            </div>
                        </div>
                    </Draggable>
                </div>
                <div className='mt-4'>
                    <MapActions />
                </div>
            </Fragment>
        )
    }
}
