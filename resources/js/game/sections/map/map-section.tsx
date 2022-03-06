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
            loading: true,
            player_kingdoms: null,
            enemy_kingdoms: null,
            time_left: 0,
        }

        // @ts-ignore
        this.mapTimeOut = Echo.private('show-timeout-move-' + this.props.user_id);
    }

    componentDidMount() {
        (new Ajax()).setRoute('map/' + this.props.character_id)
                    .doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                map_url: result.data.map_url,
                locations: result.data.locations,
                character_position: {
                    x: result.data.character_map.character_position_x,
                    y: result.data.character_map.character_position_y,
                },
                player_kingdoms: result.data.my_kingdoms,
                enemy_kingdoms: result.data.other_kingdoms,
            });
        }, (err: AxiosError) => {

        });

        this.mapTimeOut.listen('Game.Maps.Events.ShowTimeOutEvent', (event: any) => {
            this.setState({
                time_left: event.forLength,
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
            return (
                <Fragment>
                    <p>One Moment ... </p>
                </Fragment>
            );
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

                                <div className="map-x-pin" style={this.playerIcon()}></div>
                            </div>
                        </div>
                    </Draggable>
                </div>
                <div className='mt-4'>
                    <MapActions move_player={this.handleMovePlayer.bind(this)}/>
                </div>
                <div className={'mt-3'}>
                    <ProgressBar time_remaining={this.state.time_left} />
                </div>
            </Fragment>
        )
    }
}
