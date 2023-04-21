import MapState from "./map-state";
import { DateTime } from "luxon";
import MapData from "../request-types/MapData";
import MapSection from "../../../../sections/map/map-section";
import {getPortLocation} from "../location-helpers";
import {getNewXPosition, getNewYPosition} from "../map-position";

export default class MapStateManager {

    static manageState(data: MapData, component: MapSection, callback?: () => void) {
        let state = {...this.setState(data), ...{loading: false, map_id: data.character_map.game_map.id}};

        state.port_location = getPortLocation(state);

        state.map_position = {
            x: getNewXPosition(state.character_position.x, state.map_position.x, component.props.view_port),
            y: getNewYPosition(state.character_position.y, state.map_position.y, component.props.view_port),
        }

        if (state.time_left !== 0) {
            state.can_player_move = false;
        }

        // @ts-ignore
        component.setState(state, () => {
            component.props.show_celestial_fight_button(data.celestial_id)

            let position: {x: number, y: number, game_map_id?: number} = state.character_position;

            position.game_map_id = state.game_map_id;

            component.props.set_character_position(position);

            if (typeof callback !== 'undefined') {
                return callback();
            }
        });
    }

    /**
     * Sets the state of the Map component base don the data from the api call.
     *
     * @param data
     */
    static setState(data: MapData): MapState {
        return {
            map_url: data.map_url,
            map_name: data.character_map.game_map.name,
            game_map_id: data.character_map.game_map_id,
            map_position: {
                x: data.character_map.position_x, y: data.character_map.position_y
            },
            character_position: {
                x: data.character_map.character_position_x, y: data.character_map.character_position_y
            },
            locations: data.locations,
            player_kingdoms: data.my_kingdoms,
            enemy_kingdoms: data.other_kingdoms,
            npc_kingdoms: data.npc_kingdoms,
            can_player_move: data.can_move,
            time_left: parseInt(this.getTimeLeftInSeconds(data).toFixed(0)),
            characters_on_map: data.characters_on_map,
            location_with_adventures: null,
            port_location: null,
            coordinates: data.coordinates,
        }
    }

    static setMapMovementActionsState(data: any): any {
        return {
            character_position: {
                x: data.character_map.character_position_x, y: data.character_map.character_position_y
            },
            locations: data.locations,
            player_kingdoms: data.my_kingdoms,
            enemy_kingdoms: data.other_kingdoms,
            npc_kingdoms: data.npc_kingdoms,
            time_left: parseInt(this.getTimeLeftInSeconds(data).toFixed(0)),
            port_location: null,
            coordinates: data.coordinates,
        }
    }

    /**
     * Returns the seconds left.
     *
     * If a player refreshes while their timer is running for the map
     * section, then we want to figure out how many seconds they have
     * left on the time.
     *
     * This is then used to update the time_left on the state object to
     * then show the timer.
     *
     * The data passed in should be from the request object, the return type is any, but will be a number.
     * Typescript assumes everything on Luxon is "undefined".
     *
     * @param data
     * @return number
     * @type [{data: any}]
     */
    static getTimeLeftInSeconds(data: any): any {
        if (data.can_move_again_at !== null) {
            const end   = DateTime.fromISO(data.can_move_again_at);
            const start = DateTime.now();

            const timeLeft = (end.diff(start, 'seconds')).toObject()

            if (typeof timeLeft === 'undefined') {
                return 0;
            }

            return timeLeft.seconds;
        }

        return 0;
    }
}
