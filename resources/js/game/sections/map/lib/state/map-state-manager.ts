import MapState from "../../types/map-state";
import { DateTime } from "luxon";
import MapData from "../request-types/MapData";
import { getPortLocation } from "../location-helpers";
import { getNewXPosition, getNewYPosition } from "../map-position";
import Game from "../../../../game";
import MapSection from "../../map-section";

export default class MapStateManager {
    /**
     * When new Data comes into the map, via player action, rebuild the state.
     *
     * This action would be called in the MapSection Component, anytime the state would change from new data.
     *
     * @param data
     * @param component
     * @returns
     */
    static buildChangeState(
        data: MapData,
        component: MapSection | Game,
    ): MapState {
        let state: MapState = {
            ...this.setState(data),
            ...{ map_id: data.character_map.game_map.id },
        };

        state.port_location = getPortLocation(state);

        const viewPort: number =
            component instanceof MapSection
                ? component.props.view_port
                : component.state.view_port;

        state.map_position = {
            x: getNewXPosition(
                data.character_map.character_position_x,
                state.map_position.x,
            ),
            y: getNewYPosition(
                data.character_map.character_position_y,
                state.map_position.y,
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

        let position: { x: number; y: number; game_map_id?: number } =
            state.character_position;

        position.game_map_id = state.game_map_id;

        if (component instanceof MapSection) {
            component.props.set_character_position(position);
        } else {
            component.setCharacterPosition(position);
        }

        if (data.is_event_based) {
            state.character_position.x =
                data.character_map.character_position_x;
            state.character_position.y =
                data.character_map.character_position_y;

            state.is_event_based = data.is_event_based;
        }

        return state;
    }

    /**
     * Build the core state of the map data.
     *
     * - Fire off events on the Game component when needed, such as:
     *   - If there is a celestial and they are at the location, we should show the attack button.
     *   - If they are at a specific position on the map, perhaps there are features that are unlocked for them.
     *
     * @param data
     * @param component
     * @returns
     */
    static buildCoreState(data: MapData, component: Game): MapState {
        let state: MapState = {
            ...this.setState(data),
            ...{ map_id: data.character_map.game_map.id },
        };

        state.port_location = getPortLocation(state);

        state.map_position = {
            x: getNewXPosition(
                state.character_position.x,
                state.map_position.x,
            ),
            y: getNewYPosition(
                state.character_position.y,
                state.map_position.y,
            ),
        };

        if (state.time_left !== 0) {
            state.can_player_move = false;
        }

        component.updateCelestial(data.celestial_id);

        let position: { x: number; y: number; game_map_id?: number } =
            state.character_position;

        position.game_map_id = state.game_map_id;

        component.setCharacterPosition(position);

        return state;
    }

    /**
     * Sets the state of the Map component base don the data from the api call.
     *
     * @param data
     */
    static setState(data: MapData): MapState {
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
                game_map_id: data.character_map.game_map_id,
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
            is_event_based: data.is_event_based,
        };
    }

    /**
     * Set the maps movement action state.
     *
     * @param data
     * @returns
     */
    static setMapMovementActionsState(data: MapData): any {
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
            const end = DateTime.fromISO(data.can_move_again_at);
            const start = DateTime.now();

            const timeLeft = end.diff(start, "seconds").toObject();

            if (typeof timeLeft === "undefined") {
                return 0;
            }

            return timeLeft.seconds;
        }

        return 0;
    }
}
