import MapState from "./map-state";
import { DateTime } from "luxon";

export default class MapStateManager {

    /**
     * Sets the state of the Map component base don the data from the api call.
     *
     * @param data
     */
    static setState(data: any): MapState {
        return {
            map_url: data.map_url,
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
            time_left: this.getTimeLeftInSeconds(data).toFixed(0),
            characters_on_map: data.characters_on_map,
            location_with_adventures: null,
            port_location: null,
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
