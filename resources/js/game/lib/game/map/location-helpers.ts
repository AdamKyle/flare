import MapState from "../types/map/map-state";
import MapStateManager from './state/map-state';
import LocationDetails from "./types/location-details";

/**
 * Gets the current port that the player is on.
 *
 * @param mapState
 * @return location or null
 * @type [{mapState: MapState}]
 */
export const getPortLocation = (mapState: MapState | MapStateManager): LocationDetails | null => {

    if (mapState.locations === null) {
        return null;
    }

    const portLocation = mapState.locations.filter((location) => location.x === mapState.character_position.x && location.y === mapState.character_position.y && location.is_port);

    if (portLocation.length > 0) {
        return portLocation[0]
    }

    return null;
}
