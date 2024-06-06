var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
import clsx from "clsx";
import React, { Fragment } from "react";
import Snowfall from "react-snowfall";
import MapTimer from "../../components/timers/map-timer";
import ComponentLoading from "../../components/ui/loading/component-loading";
import { updateLocationBasedActions } from "../../lib/ajax/update-location-based-actions";
import { updateTimers } from "../../lib/ajax/update-timers";
import EnemyKingdoms from "../components/kingdoms/enemy-kingdoms";
import Kingdoms from "../components/kingdoms/kingdoms";
import NpcKingdoms from "../components/kingdoms/npc-kingdoms";
import Location from "../components/locations/location";
import DirectionalMovement from "./actions/directional-movement";
import MapActions from "./actions/map-actions";
import MovePlayer from "./lib/ajax/move-player";
import { getStyle, playerIconPosition } from "./lib/map-management";
import { dragMap, fetchLeftBounds } from "./lib/map-position";
import MapStateManager from "./lib/state/map-state-manager";
import { isEqual } from "lodash";
import Draggable from "react-draggable/build/web/react-draggable.min";
var MapSection = (function (_super) {
    __extends(MapSection, _super);
    function MapSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
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
        };
        _this.mapTimeOut = Echo.private("show-timeout-move-" + _this.props.user_id);
        _this.explorationTimeOut = Echo.private("exploration-timeout-" + _this.props.user_id);
        _this.celestialTimeout = Echo.private("update-character-celestial-timeout-" + _this.props.user_id);
        return _this;
    }
    MapSection.prototype.componentDidMount = function () {
        var _this = this;
        if (this.props.map_data !== null) {
            this.setState(__assign(__assign({}, this.props.map_data), { time_left: 0, automation_time_out: 0, celestial_time_out: 0 }), function () {
                updateTimers(_this.props.character_id);
                updateLocationBasedActions(_this.props.character_id);
                _this.setState({ loading: false });
            });
        }
        this.mapTimeOut.listen("Game.Maps.Events.ShowTimeOutEvent", function (event) {
            _this.setState({
                time_left: event.forLength,
                can_player_move: event.canMove,
            });
        });
        this.explorationTimeOut.listen("Game.Exploration.Events.ExplorationTimeOut", function (event) {
            _this.setState({
                automation_time_out: event.forLength,
            });
        });
        this.celestialTimeout.listen("Game.Core.Events.UpdateCharacterCelestialTimeOut", function (event) {
            _this.setState({
                celestial_time_out: event.timeLeft,
            });
        });
    };
    MapSection.prototype.componentDidUpdate = function () {
        var _this = this;
        if (this.props.map_data !== null && this.state.loading) {
            this.setState(__assign(__assign({}, this.props.map_data), { time_left: 0, automation_time_out: 0, celestial_time_out: 0 }), function () {
                updateTimers(_this.props.character_id);
                updateLocationBasedActions(_this.props.character_id);
                _this.setState({ loading: false });
            });
        }
        if (this.props.map_data !== null) {
            if (this.props.map_data.map_url !== this.state.map_url) {
                this.setState(__assign({}, this.props.map_data));
            }
            if (this.props.map_data.player_kingdoms.length !==
                this.state.player_kingdoms.length ||
                this.props.map_data.enemy_kingdoms.length !==
                    this.state.enemy_kingdoms.length ||
                this.props.map_data.npc_kingdoms.length !==
                    this.state.npc_kingdoms.length) {
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
        }
    };
    MapSection.prototype.componentWillUnmount = function () {
        this.props.set_map_data(this.state);
    };
    MapSection.prototype.setStateFromData = function (data, callback) {
        var state = MapStateManager.buildChangeState(data, this);
        this.setState(state, function () {
            if (typeof callback === "function") {
                return callback();
            }
        });
    };
    MapSection.prototype.handleDrag = function (e, position) {
        this.setState(dragMap(position, this.state.bottom_bounds, this.state.right_bounds));
    };
    MapSection.prototype.handleTeleportPlayer = function (data) {
        new MovePlayer(this).teleportPlayer(data, this.props.character_id, this.setStateFromData.bind(this));
    };
    MapSection.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(ComponentLoading, null);
        }
        return (React.createElement(Fragment, null,
            React.createElement("div", { className: "overflow-hidden max-h-[315px] max-w-[514px] sm:border-2 lg:border-0 sm:mr-auto sm:ml-auto lg:max-w-full lg:mr-0 lg:ml-0" },
                React.createElement(Draggable, { position: this.state.map_position, bounds: {
                        top: -2200,
                        left: fetchLeftBounds(this),
                        right: this.state.right_bounds,
                        bottom: this.state.bottom_bounds,
                    }, handle: ".handle", defaultPosition: { x: 0, y: 0 }, grid: [16, 16], scale: 1, onDrag: this.handleDrag.bind(this) },
                    React.createElement("div", null,
                        React.createElement("div", { className: "handle game-map", style: getStyle(this) },
                            this.state.map_name === "The Ice Plane" ? (React.createElement(Snowfall, null)) : null,
                            React.createElement(Location, { locations: this.state.locations, character_position: this.state.character_position, currencies: this.props.currencies, teleport_player: this.handleTeleportPlayer.bind(this), can_move: this.state.can_player_move, is_dead: this.props.is_dead, is_automation_running: this.props.is_automaton_running }),
                            React.createElement(Kingdoms, { kingdoms: this.state.player_kingdoms, character_id: this.props.character_id, character_position: this.state.character_position, currencies: this.props.currencies, teleport_player: this.handleTeleportPlayer.bind(this), can_move: this.state.can_player_move, is_dead: this.props.is_dead, is_automation_running: this.props.is_automaton_running }),
                            React.createElement(EnemyKingdoms, { kingdoms: this.state.enemy_kingdoms, character_id: this.props.character_id, character_position: this.state.character_position, currencies: this.props.currencies, teleport_player: this.handleTeleportPlayer.bind(this), can_move: this.state.can_player_move, is_dead: this.props.is_dead, is_automation_running: this.props.is_automaton_running }),
                            React.createElement(NpcKingdoms, { kingdoms: this.state.npc_kingdoms, character_id: this.props.character_id, character_position: this.state.character_position, currencies: this.props.currencies, teleport_player: this.handleTeleportPlayer.bind(this), can_move: this.state.can_player_move, is_dead: this.props.is_dead, is_automation_running: this.props.is_automaton_running }),
                            React.createElement("div", { className: "map-x-pin", style: playerIconPosition(this) }))))),
            React.createElement("div", { className: "mt-4" },
                React.createElement("div", { className: "my-4 grid grid-cols-2 gap-2" },
                    React.createElement("div", null,
                        "X/Y: ",
                        this.state.character_position.x,
                        " /",
                        " ",
                        this.state.character_position.y),
                    React.createElement("div", null,
                        "Plane:",
                        " ",
                        React.createElement("a", { href: "/information/map/" + this.state.map_id, target: "_blank" },
                            this.state.map_name,
                            " ",
                            React.createElement("i", { className: "fas fa-external-link-alt" })))),
                React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2" }),
                React.createElement("div", null,
                    "Character on Plane: ",
                    this.state.characters_on_map),
                React.createElement("div", { className: "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2" }),
                React.createElement(MapActions, { character_id: this.props.character_id, can_move: this.state.can_player_move, is_dead: this.props.is_dead, is_automation_running: this.props.is_automaton_running, can_engage_celestial: this.props.can_engage_celestial, port_location: this.state.port_location, locations: this.state.locations, player_kingdoms: this.state.player_kingdoms, enemy_kingdoms: this.state.enemy_kingdoms, npc_kingdoms: this.state.npc_kingdoms, character_position: this.state.character_position, character_currencies: this.props.currencies, coordinates: this.state.coordinates, view_port: this.props.view_port, update_map_state: this.setStateFromData.bind(this), map_id: this.state.map_id }),
                React.createElement("div", { className: clsx("border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2", {
                        hidden: this.props.view_port >= 1600,
                    }) }),
                React.createElement(DirectionalMovement, { character_position: this.state.character_position, map_position: this.state.map_position, view_port: this.props.view_port, is_dead: this.props.is_dead, is_automation_running: this.props.is_automaton_running, character_id: this.props.character_id, map_id: this.state.map_id, update_map_state: this.setStateFromData.bind(this), can_move: this.state.can_player_move })),
            React.createElement("div", { className: clsx("mt-4", {
                    hidden: this.props.disable_bottom_timer,
                }) },
                React.createElement(MapTimer, { time_left: this.state.time_left, automation_time_out: this.state.automation_time_out, celestial_time_out: this.state.celestial_time_out }))));
    };
    return MapSection;
}(React.Component));
export default MapSection;
//# sourceMappingURL=map-section.js.map