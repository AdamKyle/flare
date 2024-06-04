var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import { DateTime } from "luxon";
import { getPortLocation } from "../location-helpers";
import { getNewXPosition, getNewYPosition } from "../map-position";
import MapSection from "../../map-section";
var MapStateManager = (function () {
    function MapStateManager() {}
    MapStateManager.buildChangeState = function (data, component) {
        var state = __assign(__assign({}, this.setState(data)), {
            map_id: data.character_map.game_map.id,
        });
        state.port_location = getPortLocation(state);
        var viewPort =
            component instanceof MapSection
                ? component.props.view_port
                : component.state.view_port;
        state.map_position = {
            x: getNewXPosition(
                state.character_position.x,
                state.map_position.x,
                viewPort,
            ),
            y: getNewYPosition(
                state.character_position.y,
                state.map_position.y,
                viewPort,
            ),
        };
        if (state.time_left !== 0) {
            state.can_player_move = false;
        }
        if (component instanceof MapSection) {
            component.props.show_celestial_fight_button(data.celestial_id);
        } else {
            component.updateCelestial(data.celestial_id);
        }
        var position = state.character_position;
        position.game_map_id = state.game_map_id;
        if (component instanceof MapSection) {
            component.props.set_character_position(position);
        } else {
            component.setCharacterPosition(position);
        }
        return state;
    };
    MapStateManager.buildCoreState = function (data, component) {
        var state = __assign(__assign({}, this.setState(data)), {
            map_id: data.character_map.game_map.id,
        });
        state.port_location = getPortLocation(state);
        state.map_position = {
            x: getNewXPosition(
                state.character_position.x,
                state.map_position.x,
                component.state.view_port,
            ),
            y: getNewYPosition(
                state.character_position.y,
                state.map_position.y,
                component.state.view_port,
            ),
        };
        if (state.time_left !== 0) {
            state.can_player_move = false;
        }
        component.updateCelestial(data.celestial_id);
        var position = state.character_position;
        position.game_map_id = state.game_map_id;
        component.setCharacterPosition(position);
        return state;
    };
    MapStateManager.setState = function (data) {
        return {
            map_id: data.character_map.id,
            map_url: data.map_url,
            map_name: data.character_map.game_map.name,
            game_map_id: data.character_map.game_map_id,
            map_position: {
                x: data.character_map.position_x,
                y: data.character_map.position_y,
            },
            character_position: {
                x: data.character_map.character_position_x,
                y: data.character_map.character_position_y,
            },
            locations: data.locations,
            player_kingdoms: data.my_kingdoms,
            enemy_kingdoms: data.other_kingdoms,
            npc_kingdoms: data.npc_kingdoms,
            can_player_move: data.can_move,
            time_left: parseInt(this.getTimeLeftInSeconds(data).toFixed(0)),
            characters_on_map: data.characters_on_map,
            port_location: null,
            coordinates: data.coordinates,
            bottom_bounds: 0,
            right_bounds: 0,
            loading: false,
            automation_time_out: 0,
            celestial_time_out: 0,
        };
    };
    MapStateManager.setMapMovementActionsState = function (data) {
        return {
            character_position: {
                x: data.character_map.character_position_x,
                y: data.character_map.character_position_y,
            },
            locations: data.locations,
            player_kingdoms: data.my_kingdoms,
            enemy_kingdoms: data.other_kingdoms,
            npc_kingdoms: data.npc_kingdoms,
            time_left: parseInt(this.getTimeLeftInSeconds(data).toFixed(0)),
            port_location: null,
            coordinates: data.coordinates,
        };
    };
    MapStateManager.getTimeLeftInSeconds = function (data) {
        if (data.can_move_again_at !== null) {
            var end = DateTime.fromISO(data.can_move_again_at);
            var start = DateTime.now();
            var timeLeft = end.diff(start, "seconds").toObject();
            if (typeof timeLeft === "undefined") {
                return 0;
            }
            return timeLeft.seconds;
        }
        return 0;
    };
    return MapStateManager;
})();
export default MapStateManager;
//# sourceMappingURL=map-state-manager.js.map
