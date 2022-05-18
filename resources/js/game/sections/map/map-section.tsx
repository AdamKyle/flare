import React, {Fragment} from "react";
import {AxiosError, AxiosResponse} from "axios";
import {dragMap, getNewXPosition, getNewYPosition} from "../../lib/game/map/map-position";
import MapState from "../../lib/game/types/map/map-state";
import MapProps from '../../lib/game/types/map/map-props';
import Ajax from "../../lib/ajax/ajax";
import Location from "../components/locations/location";
import MapActions from "../components/actions/map/map-actions";
import Kingdoms from "../components/kingdoms/kingdoms";
import TimerProgressBar from "../../components/ui/progress-bars/timer-progress-bar";
import EnemyKingdoms from "../components/kingdoms/enemy-kingdoms";
import MovePlayer from "../../lib/game/map/ajax/move-player";
import MapStateManager from "../../lib/game/map/state/map-state-manager";
import NpcKingdoms from "../components/kingdoms/npc-kingdoms";
import ComponentLoading from "../../components/ui/loading/component-loading";
import {getPortLocation} from "../../lib/game/map/location-helpers";
// @ts-ignore
import Draggable from 'react-draggable/build/web/react-draggable.min';

export default class MapSection extends React.Component<MapProps, MapState> {

    private mapTimeOut: any;

    private traverseUpdate: any;

    private explorationTimeOut: any;

    constructor(props: MapProps) {
        super(props);

        this.state = {
            map_url: '',
            map_id: 0,
            map_position: {
                x: 0, y: 0
            },
            character_position: {
                x: 0, y: 0
            },
            bottom_bounds: 0,
            right_bounds: 0,
            locations: null,
            port_location: null,
            loading: true,
            player_kingdoms: null,
            enemy_kingdoms: null,
            npc_kingdoms: null,
            coordinates: null,
            can_player_move: true,
            characters_on_map: 0,
            time_left: 0,
            automation_time_out: 0,
        }

        // @ts-ignore
        this.mapTimeOut         = Echo.private('show-timeout-move-' + this.props.user_id);

        // @ts-ignore
        this.explorationTimeOut = Echo.private('exploration-timeout-' + this.props.user_id);

        // @ts-ignore
        this.traverseUpdate     = Echo.private('update-map-plane-' + this.props.user_id);
    }

    componentDidMount() {
        (new Ajax()).setRoute('map/' + this.props.character_id)
                    .doAjaxCall('get', (result: AxiosResponse) => {

            this.setStateFromData(result.data, () => {
                if (this.props.automation_completed_at !== 0) {
                    this.setState({
                        automation_time_out: this.props.automation_completed_at
                    })
                }
            });
        }, (err: AxiosError) => {

        });

        this.mapTimeOut.listen('Game.Maps.Events.ShowTimeOutEvent', (event: any) => {
            this.setState({
                time_left: event.forLength,
                can_player_move: event.canMove,
            });
        });

        this.traverseUpdate.listen('Game.Maps.Events.UpdateMapBroadcast', (event: any) => {
            this.setStateFromData(event.mapDetails);
        });

        this.explorationTimeOut.listen('Game.Exploration.Events.ExplorationTimeOut', (event: any) => {
            this.setState({
                time_left: event.forLength,
            });
        });
    }

    setStateFromData(data: any, callback?: () => void) {
        let state = {...MapStateManager.setState(data), ...{loading: false, map_id: data.character_map.game_map.id}};

        state.port_location = getPortLocation(state);

        state.map_position = {
            x: getNewXPosition(state.character_position.x, state.map_position.x, this.props.view_port),
            y: getNewYPosition(state.character_position.y, state.map_position.y, this.props.view_port),
        }

        if (state.time_left !== 0) {
            state.can_player_move = false;
        }

        // @ts-ignore
        this.setState(state, () => {
            if (typeof callback !== 'undefined') {
                return callback();
            }
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

        return this.state.locations.filter((location) => location.is_port);
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
                              .movePlayer(this.props.character_id, direction, this.props.view_port);
    }

    handleTeleportPlayer(data: {x: number, y: number, cost: number, timeout: number}) {
        (new MovePlayer(this)).teleportPlayer(data, this.props.character_id, this.props.view_port);
    }

    handleSetSail(data: {x: number, y: number, cost: number, timeout: number}) {
        (new MovePlayer(this)).setSail(data, this.props.character_id, this.props.view_port);
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />
        }

        return(
            <Fragment>
                <div className='overflow-hidden max-h-[300px]'>
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

                                <Location locations={this.state.locations} character_position={this.state.character_position} currencies={this.props.currencies} teleport_player={this.handleTeleportPlayer.bind(this)} />

                                <Kingdoms kingdoms={this.state.player_kingdoms} character_id={this.props.character_id} character_position={this.state.character_position} currencies={this.props.currencies} teleport_player={this.handleTeleportPlayer.bind(this)}/>

                                <EnemyKingdoms kingdoms={this.state.enemy_kingdoms} character_id={this.props.character_id} character_position={this.state.character_position} currencies={this.props.currencies} teleport_player={this.handleTeleportPlayer.bind(this)}/>

                                <NpcKingdoms kingdoms={this.state.npc_kingdoms} character_id={this.props.character_id} character_position={this.state.character_position} currencies={this.props.currencies} teleport_player={this.handleTeleportPlayer.bind(this)} />

                                <div className="map-x-pin" style={this.playerIcon()}></div>
                            </div>
                        </div>
                    </Draggable>
                </div>
                <div className='mt-4'>
                    <MapActions move_player={this.handleMovePlayer.bind(this)}
                                teleport_player={this.handleTeleportPlayer.bind(this)}
                                set_sail={this.handleSetSail.bind(this)}
                                can_player_move={this.state.can_player_move}
                                players_on_map={this.state.characters_on_map}
                                port_location={this.state.port_location}
                                ports={this.fetchPorts()}
                                coordinates={this.state.coordinates}
                                character_position={this.state.character_position}
                                currencies={this.props.currencies}
                                locations={this.state.locations}
                                player_kingdoms={this.state.player_kingdoms}
                                character_id={this.props.character_id}
                                enemy_kingdoms={this.state.enemy_kingdoms}
                                npc_kingdoms={this.state.npc_kingdoms}
                                view_port={this.props.view_port}
                                is_dead={this.props.is_dead}
                                map_id={this.state.map_id}
                    />
                </div>
                <div className={'mt-3'}>
                    {
                        this.state.automation_time_out !== 0 ?
                            <Fragment>
                                <div className='grid grid-cols-2 gap-2'>
                                    <TimerProgressBar time_remaining={this.state.time_left} time_out_label={'Movement Timeout'}/>
                                    <TimerProgressBar time_remaining={this.state.automation_time_out} time_out_label={'Exploration'}/>
                                </div>
                            </Fragment>
                        :
                            <TimerProgressBar time_remaining={this.state.time_left} time_out_label={'Movement Timeout'}/>
                    }

                </div>
            </Fragment>
        )
    }
}
