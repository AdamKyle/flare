import React, {Fragment} from "react";
import {AxiosError, AxiosResponse} from "axios";
import Draggable from 'react-draggable';
import {dragMap} from "../../lib/game/map/map-position";
import MapState from "../../lib/game/types/map/map-state";
import MapProps from '../../lib/game/types/map/map-props';
import Ajax from "../../lib/ajax/ajax";
import Location from "../components/locations/location";
import MapActions from "../components/actions/map/map-actions";
import Kingdoms from "../components/kingdoms/kingdoms";
import ProgressBar from "../../components/ui/progress-bars/progress-bar";
import EnemyKingdoms from "../components/kingdoms/enemy-kingdoms";
import MovePlayer from "../../lib/game/map/ajax/move-player";
import MapStateManager from "../../lib/game/map/state/map-state-manager";
import NpcKingdoms from "../components/kingdoms/npc-kingdoms";
import ComponentLoading from "../../components/ui/loading/component-loading";
import {getLocationWithAdventures, getPortLocation} from "../../lib/game/map/location-helpers";

export default class MapSection extends React.Component<MapProps, MapState> {

    private mapTimeOut: any;

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
            location_with_adventures: null,
            port_location: null,
            loading: true,
            player_kingdoms: null,
            enemy_kingdoms: null,
            npc_kingdoms: null,
            coordinates: null,
            can_player_move: true,
            characters_on_map: 0,
            time_left: 0,
        }

        // @ts-ignore
        this.mapTimeOut = Echo.private('show-timeout-move-' + this.props.user_id);
    }

    componentDidMount() {
        (new Ajax()).setRoute('map/' + this.props.character_id)
                    .doAjaxCall('get', (result: AxiosResponse) => {

            let state = {...MapStateManager.setState(result.data), ...{loading: false}};

            state.location_with_adventures = getLocationWithAdventures(state);
            state.port_location = getPortLocation(state);

            this.setState(state);
        }, (err: AxiosError) => {

        });

        this.mapTimeOut.listen('Game.Maps.Events.ShowTimeOutEvent', (event: any) => {
            this.setState({
                time_left: event.forLength,
                can_player_move: event.canMove,
            });
        });
    }

    fetchLeftBounds(): number {

        if (this.props.view_port >= 1920) {
            return 0;
        }

        if (this.props.view_port < 400) {
            return -260;
        }

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

    fetchPorts() {

        if (this.state.locations === null) {
            return null;
        }

        return this.state.locations.filter((location) => location.is_port && location.x !== this.state.character_position.x && location.y !== this.state.character_position.y);
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

    getStyle(): { backgroundImage: string, height: number, backgroundRepeat: string, width?: number } {
        if ((this.props.view_port > 770 && this.props.view_port < 1600) || this.props.view_port >= 1920) {
            return {backgroundImage: `url("${this.state.map_url}")`, backgroundRepeat: 'no-repeat', height: 500};
        }

        return {backgroundImage: `url("${this.state.map_url}")`, backgroundRepeat: 'no-repeat', height: 500, width: 500};
    }

    handleMovePlayer(direction: string) {
        (new MovePlayer(this)).setCharacterPosition(this.state.character_position)
                              .setMapPosition(this.state.map_position)
                              .movePlayer(this.props.character_id, direction);
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />
        }

        return(
            <Fragment>
                <div className='overflow-hidden max-h-[350px] md:ml-[20px]'>
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

                                <Kingdoms kingdoms={this.state.player_kingdoms} character_id={this.props.character_id}/>

                                <EnemyKingdoms kingdoms={this.state.enemy_kingdoms} character_id={this.props.character_id}/>

                                <NpcKingdoms kingdoms={this.state.npc_kingdoms}/>

                                <div className="map-x-pin" style={this.playerIcon()}></div>
                            </div>
                        </div>
                    </Draggable>
                </div>
                <div className='mt-4'>
                    <MapActions move_player={this.handleMovePlayer.bind(this)}
                                can_player_move={this.state.can_player_move}
                                players_on_map={this.state.characters_on_map}
                                location_with_adventures={this.state.location_with_adventures}
                                port_location={this.state.port_location}
                                ports={this.fetchPorts()}
                                coordinates={this.state.coordinates}
                                character_position={this.state.character_position}
                                currencies={this.props.currencies}
                    />
                </div>
                <div className={'mt-3'}>
                    <ProgressBar time_remaining={this.state.time_left} />
                </div>
            </Fragment>
        )
    }
}
