import clsx from "clsx";
import React, { Fragment } from "react";
import Snowfall from "react-snowfall";
import MapTimer from "../../components/timers/map-timer";
import ComponentLoading from "../../components/ui/loading/component-loading";
import { updateLocationBasedActions } from "../../lib/ajax/update-location-based-actions";
import { updateTimers } from "../../lib/ajax/update-timers";
import Location from "../components/locations/location";
import DirectionalMovement from "./actions/directional-movement";
import MapActions from "./actions/map-actions";
import MovePlayer from "./lib/ajax/move-player";
import { getStyle, playerIconPosition } from "./lib/map-management";
import {
    dragMap,
    fetchLeftBounds,
    getNewXPosition,
    getNewYPosition,
} from "./lib/map-position";
import MapData from "./lib/request-types/MapData";
import MapStateManager from "./lib/state/map-state-manager";
import MapState from "./types/map-state";
import MapProps from "./types/map/map-props";
import { isEqual } from "lodash";
import NpcKingdoms from "../../components/kingdoms/map-pins/npc-kingdoms";
import EnemyKingdoms from "../../components/kingdoms/map-pins/enemy-kingdoms";
import Kingdoms from "../../components/kingdoms/map-pins/kingdoms";

// @ts-ignore
import Draggable from "react-draggable/build/web/react-draggable.min";

export default class MapSection extends React.Component<MapProps, MapState> {
    private mapTimeOut: any;

    private explorationTimeOut: any;

    private celestialTimeout: any;

    constructor(props: MapProps) {
        super(props);

        this.state = {
            map_url: "",
            map_id: 0,
            map_name: "",
            map_position: {
                x: 0,
                y: 0,
            },
            character_position: {
                x: 0,
                y: 0,
                game_map_id: 0,
            },
            game_map_id: 0,
            bottom_bounds: 0,
            right_bounds: 0,
            locations: [],
            port_location: null,
            loading: true,
            player_kingdoms: [],
            enemy_kingdoms: [],
            npc_kingdoms: [],
            coordinates: null,
            can_player_move: true,
            characters_on_map: 0,
            time_left: 0,
            automation_time_out: 0,
            celestial_time_out: 0,
            is_event_based: false,
        };

        // @ts-ignore
        this.mapTimeOut = Echo.private(
            "show-timeout-move-" + this.props.user_id,
        );

        // @ts-ignore
        this.explorationTimeOut = Echo.private(
            "exploration-timeout-" + this.props.user_id,
        );

        // @ts-ignore
        this.celestialTimeout = Echo.private(
            "update-character-celestial-timeout-" + this.props.user_id,
        );
    }

    componentDidMount() {
        if (this.props.map_data !== null) {
            this.setState(
                {
                    ...this.props.map_data,
                    time_left: 0,
                    automation_time_out: 0,
                    celestial_time_out: 0,
                },
                () => {
                    updateTimers(this.props.character_id);

                    updateLocationBasedActions(this.props.character_id);

                    this.setState({ loading: false });
                },
            );
        }

        this.mapTimeOut.listen(
            "Game.Maps.Events.ShowTimeOutEvent",
            (event: any) => {
                this.setState({
                    time_left: event.forLength,
                    can_player_move: event.canMove,
                });
            },
        );

        this.explorationTimeOut.listen(
            "Game.Exploration.Events.ExplorationTimeOut",
            (event: any) => {
                this.setState({
                    automation_time_out: event.forLength,
                });
            },
        );

        this.celestialTimeout.listen(
            "Game.Core.Events.UpdateCharacterCelestialTimeOut",
            (event: any) => {
                this.setState({
                    celestial_time_out: event.timeLeft,
                });
            },
        );
    }

    componentDidUpdate(): void {
        if (this.props.map_data !== null && this.state.loading) {
            this.setState(
                {
                    ...this.props.map_data,
                    time_left: 0,
                    automation_time_out: 0,
                    celestial_time_out: 0,
                },
                () => {
                    updateTimers(this.props.character_id);

                    updateLocationBasedActions(this.props.character_id);

                    this.setState({ loading: false });
                },
            );
        }

        if (this.props.map_data !== null) {
            if (this.props.map_data.map_url !== this.state.map_url) {
                this.setState({ ...this.props.map_data });
            }

            if (
                this.props.map_data.player_kingdoms.length !==
                    this.state.player_kingdoms.length ||
                this.props.map_data.enemy_kingdoms.length !==
                    this.state.enemy_kingdoms.length ||
                this.props.map_data.npc_kingdoms.length !==
                    this.state.npc_kingdoms.length
            ) {
                this.setState({
                    player_kingdoms: this.props.map_data.player_kingdoms,
                    enemy_kingdoms: this.props.map_data.enemy_kingdoms,
                    npc_kingdoms: this.props.map_data.npc_kingdoms,
                });
            }

            if (!isEqual(this.props.map_data.locations, this.state.locations)) {
                this.setState({
                    locations: this.props.map_data.locations,
                });
            }

            if (this.state.can_player_move !== this.props.can_move) {
                this.setState({ can_player_move: this.props.can_move });
            }

            if (
                this.props.map_data.is_event_based !==
                    this.state.is_event_based &&
                !isEqual(
                    this.props.map_data.character_position,
                    this.state.character_position,
                )
            ) {
                this.setState({
                    character_position: this.props.map_data.character_position,
                    is_event_based: this.state.is_event_based,
                    map_position: this.props.map_data.map_position,
                });
            }
        }
    }

    componentWillUnmount(): void {
        this.props.set_map_data(this.state);
    }

    setStateFromData(data: MapData, callback?: () => void) {
        const state = MapStateManager.buildChangeState(data, this);

        this.setState(state, () => {
            if (typeof callback === "function") {
                return callback();
            }
        });
    }

    handleDrag(e: MouseEvent, position: { x: number; y: number }) {
        this.setState(
            dragMap(
                position,
                this.state.bottom_bounds,
                this.state.right_bounds,
            ),
        );
    }

    handleTeleportPlayer(data: {
        x: number;
        y: number;
        cost: number;
        timeout: number;
    }) {
        new MovePlayer(this).teleportPlayer(
            data,
            this.props.character_id,
            this.setStateFromData.bind(this),
        );
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />;
        }

        return (
            <Fragment>
                <div className="overflow-hidden max-h-[315px] max-w-[514px] sm:border-2 lg:border-0 sm:mr-auto sm:ml-auto lg:max-w-full lg:mr-0 lg:ml-0">
                    <Draggable
                        position={this.state.map_position}
                        bounds={{
                            top: -2200,
                            left: fetchLeftBounds(this),
                            right: this.state.right_bounds,
                            bottom: this.state.bottom_bounds,
                        }}
                        handle=".handle"
                        defaultPosition={{ x: 0, y: 0 }}
                        grid={[16, 16]}
                        scale={1}
                        onDrag={this.handleDrag.bind(this)}
                    >
                        <div>
                            <div
                                className="handle game-map"
                                style={getStyle(this)}
                            >
                                {this.state.map_name === "The Ice Plane" ? (
                                    <Snowfall />
                                ) : null}

                                <Location
                                    locations={this.state.locations}
                                    character_position={
                                        this.state.character_position
                                    }
                                    currencies={this.props.currencies}
                                    teleport_player={this.handleTeleportPlayer.bind(
                                        this,
                                    )}
                                    can_move={this.state.can_player_move}
                                    is_dead={this.props.is_dead}
                                    is_automation_running={
                                        this.props.is_automaton_running
                                    }
                                />

                                <Kingdoms
                                    kingdoms={this.state.player_kingdoms}
                                    character_id={this.props.character_id}
                                    character_position={
                                        this.state.character_position
                                    }
                                    currencies={this.props.currencies}
                                    teleport_player={this.handleTeleportPlayer.bind(
                                        this,
                                    )}
                                    can_move={this.state.can_player_move}
                                    is_dead={this.props.is_dead}
                                    is_automation_running={
                                        this.props.is_automaton_running
                                    }
                                />

                                <EnemyKingdoms
                                    kingdoms={this.state.enemy_kingdoms}
                                    character_id={this.props.character_id}
                                    character_position={
                                        this.state.character_position
                                    }
                                    currencies={this.props.currencies}
                                    teleport_player={this.handleTeleportPlayer.bind(
                                        this,
                                    )}
                                    can_move={this.state.can_player_move}
                                    is_dead={this.props.is_dead}
                                    is_automation_running={
                                        this.props.is_automaton_running
                                    }
                                />

                                <NpcKingdoms
                                    kingdoms={this.state.npc_kingdoms}
                                    character_id={this.props.character_id}
                                    character_position={
                                        this.state.character_position
                                    }
                                    currencies={this.props.currencies}
                                    teleport_player={this.handleTeleportPlayer.bind(
                                        this,
                                    )}
                                    can_move={this.state.can_player_move}
                                    is_dead={this.props.is_dead}
                                    is_automation_running={
                                        this.props.is_automaton_running
                                    }
                                />

                                <div
                                    className="map-x-pin"
                                    style={playerIconPosition(this)}
                                ></div>
                            </div>
                        </div>
                    </Draggable>
                </div>
                <div className="mt-4">
                    <div className="my-4 grid grid-cols-2 gap-2">
                        <div>
                            X/Y: {this.state.character_position.x} /{" "}
                            {this.state.character_position.y}
                        </div>
                        <div>
                            Plane:{" "}
                            <a
                                href={"/information/map/" + this.state.map_id}
                                target="_blank"
                            >
                                {this.state.map_name}{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                    <div>
                        Character on Plane: {this.state.characters_on_map}
                    </div>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                    <MapActions
                        character_id={this.props.character_id}
                        can_move={this.state.can_player_move}
                        is_dead={this.props.is_dead}
                        is_automation_running={this.props.is_automaton_running}
                        can_engage_celestial={this.props.can_engage_celestial}
                        port_location={this.state.port_location}
                        locations={this.state.locations}
                        player_kingdoms={this.state.player_kingdoms}
                        enemy_kingdoms={this.state.enemy_kingdoms}
                        npc_kingdoms={this.state.npc_kingdoms}
                        character_position={this.state.character_position}
                        character_currencies={this.props.currencies}
                        coordinates={this.state.coordinates}
                        view_port={this.props.view_port}
                        update_map_state={this.setStateFromData.bind(this)}
                        map_id={this.state.map_id}
                    />
                    <div
                        className={clsx(
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                            {
                                hidden: this.props.view_port >= 1600,
                            },
                        )}
                    ></div>
                    <DirectionalMovement
                        character_position={this.state.character_position}
                        map_position={this.state.map_position}
                        view_port={this.props.view_port}
                        is_dead={this.props.is_dead}
                        is_automation_running={this.props.is_automaton_running}
                        character_id={this.props.character_id}
                        map_id={this.state.map_id}
                        update_map_state={this.setStateFromData.bind(this)}
                        can_move={this.state.can_player_move}
                    />
                </div>
                <div
                    className={clsx("mt-4", {
                        hidden: this.props.disable_bottom_timer,
                    })}
                >
                    <MapTimer
                        time_left={this.state.time_left}
                        automation_time_out={this.state.automation_time_out}
                        celestial_time_out={this.state.celestial_time_out}
                    />
                </div>
            </Fragment>
        );
    }
}
