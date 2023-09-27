import React, { Fragment } from "react";
import { dragMap, fetchLeftBounds } from "../../lib/game/map/map-position";
import MapProps from "../../lib/game/types/map/map-props";
import Location from "../components/locations/location";
import Kingdoms from "../components/kingdoms/kingdoms";
import EnemyKingdoms from "../components/kingdoms/enemy-kingdoms";
import MovePlayer from "../../lib/game/map/ajax/move-player";
import MapStateManager from "../../lib/game/map/state/map-state-manager";
import NpcKingdoms from "../components/kingdoms/npc-kingdoms";
import ComponentLoading from "../../components/ui/loading/component-loading";
import MapData from "../../lib/game/map/request-types/MapData";
import {
    getStyle,
    playerIconPosition,
} from "../../lib/game/map/map-management";
import MapTimer from "./map-timer";
import DirectionalMovement from "./actions/directional-movement";
import MapActions from "./actions/map-actions";
import NpcKingdomsDetails from "../../lib/game/types/map/npc-kingdoms-details";
import PlayerKingdomsDetails from "../../lib/game/types/map/player-kingdoms-details";
import clsx from "clsx";
// @ts-ignore
import Draggable from "react-draggable/build/web/react-draggable.min";
import MapState from "./types/map-state";
import { DateTime } from "luxon";
import { getRemainingTime } from "../../lib/helpers/time-left-seconds";

export default class MapSection extends React.Component<MapProps, MapState> {
    private mapTimeOut: any;

    private traverseUpdate: any;

    private explorationTimeOut: any;

    private globalMapUpdate: any;

    private kingdomsUpdate: any;

    private npcKingdomsUpdate: any;

    private celestialTimeout: any;

    private corruptedLocations: any;

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
        };

        // @ts-ignore
        this.mapTimeOut = Echo.private(
            "show-timeout-move-" + this.props.user_id
        );

        // @ts-ignore
        this.globalMapUpdate = Echo.join("global-map-update");

        // @ts-ignore
        this.kingdomsUpdate = Echo.private(
            "add-kingdom-to-map-" + this.props.user_id
        );

        // @ts-ignore
        this.explorationTimeOut = Echo.private(
            "exploration-timeout-" + this.props.user_id
        );

        // @ts-ignore
        this.traverseUpdate = Echo.private(
            "update-plane-" + this.props.user_id
        );

        // @ts-ignore
        this.npcKingdomsUpdate = Echo.join("npc-kingdoms-update");

        // @ts-ignore
        this.celestialTimeout = Echo.private(
            "update-character-celestial-timeout-" + this.props.user_id
        );

        // @ts-ignore
        this.corruptedLocations = Echo.join("corrupt-locations");
    }

    componentDidMount() {
        if (this.props.map_data !== null) {
            this.setState({ ...this.props.map_data }, () => {
                this.updateTimers();

                this.setState({ loading: false });
            });
        }

        this.mapTimeOut.listen(
            "Game.Maps.Events.ShowTimeOutEvent",
            (event: any) => {
                this.setState({
                    time_left: event.forLength,
                    can_player_move: event.canMove,
                });
            }
        );

        this.traverseUpdate.listen(
            "Game.Maps.Events.UpdateMap",
            (event: any) => {
                this.setStateFromData(event.mapDetails);

                this.props.update_character_quests_plane(
                    event.mapDetails.character_map.game_map.name
                );
            }
        );

        this.globalMapUpdate.listen(
            "Game.Kingdoms.Events.UpdateGlobalMap",
            (event: any) => {
                const playerKingdomsFilter = this.state.player_kingdoms.filter(
                    (playerKingdom: PlayerKingdomsDetails) => {
                        if (
                            !event.npcKingdoms.some(
                                (kingdom: NpcKingdomsDetails) =>
                                    kingdom.id === playerKingdom.id
                            )
                        ) {
                            return playerKingdom;
                        }
                    }
                );

                this.setState({
                    enemy_kingdoms: event.otherKingdoms.filter(
                        (kingdom: PlayerKingdomsDetails) =>
                            kingdom.character_id !== this.props.character_id
                    ),
                    npc_kingdoms: event.npcKingdoms,
                    player_kingdoms: playerKingdomsFilter,
                });
            }
        );

        this.explorationTimeOut.listen(
            "Game.Exploration.Events.ExplorationTimeOut",
            (event: any) => {
                this.setState({
                    automation_time_out: event.forLength,
                });
            }
        );

        this.kingdomsUpdate.listen(
            "Game.Kingdoms.Events.AddKingdomToMap",
            (event: any) => {
                this.setState({
                    player_kingdoms: event.myKingdoms,
                });
            }
        );

        this.npcKingdomsUpdate.listen(
            "Game.Kingdoms.Events.UpdateNPCKingdoms",
            (event: {
                npcKingdoms: NpcKingdomsDetails[] | [];
                mapName: string;
            }) => {
                if (this.state.map_name === event.mapName) {
                    this.setState({
                        npc_kingdoms: event.npcKingdoms,
                    });
                }
            }
        );

        this.celestialTimeout.listen(
            "Game.Core.Events.UpdateCharacterCelestialTimeOut",
            (event: any) => {
                this.setState({
                    celestial_time_out: event.timeLeft,
                });
            }
        );

        this.corruptedLocations.listen(
            "Game.Raids.Events.CorruptLocations",
            (event: any) => {
                let locations = JSON.parse(
                    JSON.stringify(this.state.locations)
                );

                locations = [...locations, ...event.corruptedLocations];

                this.setState({
                    locations: locations,
                });
            }
        );
    }

    componentDidUpdate(): void {
        if (this.props.map_data !== null && this.state.loading) {
            this.setState({ ...this.props.map_data }, () => {
                this.updateTimers();

                this.setState({ loading: false });
            });
        }
    }

    componentWillUnmount(): void {
        this.props.update_map_timer_data({
            time_left: this.state.time_left,
            automation_time_out: this.state.automation_time_out,
            celestial_time_out: this.state.celestial_time_out,

            time_left_started:
                this.state.time_left > 0 ? DateTime.local().toSeconds() : 0,
            automation_time_out_started:
                this.state.automation_time_out > 0
                    ? DateTime.local().toSeconds()
                    : 0,
            celestial_time_out_started:
                this.state.celestial_time_out > 0
                    ? DateTime.local().toSeconds()
                    : 0,
        });
    }

    updateTimers() {
        this.setState({
            celestial_time_out: getRemainingTime(
                this.props.can_engage_celestials_again_at,
                this.props.map_timer_data.celestial_time_out_started
            ),
            automation_time_out: getRemainingTime(
                this.props.automation_completed_at,
                this.props.map_timer_data.automation_time_out_started
            ),
            time_left: getRemainingTime(
                this.props.map_timer_data.time_left,
                this.props.map_timer_data.time_left_started
            ),
        });
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
            dragMap(position, this.state.bottom_bounds, this.state.right_bounds)
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
            this.setStateFromData.bind(this)
        );
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />;
        }

        return (
            <Fragment>
                <div className="overflow-hidden max-h-[315px] sm:max-w-[75%] md:max-w-[44%] sm:border-2 lg:border-0 sm:mr-auto sm:ml-auto lg:max-w-full lg:mr-0 lg:ml-0">
                    <Draggable
                        position={this.state.map_position}
                        bounds={{
                            top: -200,
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
                                <Location
                                    locations={this.state.locations}
                                    character_position={
                                        this.state.character_position
                                    }
                                    currencies={this.props.currencies}
                                    teleport_player={this.handleTeleportPlayer.bind(
                                        this
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
                                        this
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
                                        this
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
                                        this
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
                            }
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
