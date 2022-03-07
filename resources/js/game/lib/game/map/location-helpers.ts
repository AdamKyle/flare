import MapState from "../types/map/map-state";
import MapStateManager from './state/map-state';

/**
 * Gets a location that contains adventures based on the characters current position.
 *
 * @param mapState
 * @return location or null
 * @type [{mapState: MapState}]
 */
export const getLocationWithAdventures = (mapState: MapState | MapStateManager): {id: number, is_port: boolean, x: number, y: number, name: string, adventures: {name: string, id: number}[]} | null => {

    if (mapState.locations === null) {
        return null;
    }

    const locationWithAdventure = mapState.locations.filter((location) => location.x === mapState.character_position.x && location.y === mapState.character_position.y);

    if (locationWithAdventure.length > 0) {
        return locationWithAdventure[0]
    }

    return null;
}

/**
 * Gets the current port that the player is on.
 *
 * @param mapState
 * @return location or null
 * @type [{mapState: MapState}]
 */
export const getPortLocation = (mapState: MapState | MapStateManager): {id: number, is_port: boolean, x: number, y: number, name: string, adventures: {name: string, id: number}[]} | null => {

    if (mapState.locations === null) {
        return null;
    }

    const portLocation = mapState.locations.filter((location) => location.x === mapState.character_position.x && location.y === mapState.character_position.y && location.is_port);

    if (portLocation.length > 0) {
        return portLocation[0]
    }

    return null;
}
