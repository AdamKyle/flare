import MapState from "../types/map-state";
import LocationDetails from "../types/location-details";
import PlayerKingdomsDetails from "../types/map/player-kingdoms-details";
import NpcKingdomsDetails from "../types/map/npc-kingdoms-details";
import MapActions from "../actions/map-actions";

/**
 * Gets the current port that the player is on.
 *
 * @param mapState
 * @return location or null
 * @type [{mapState: MapState}]
 */
export const getPortLocation = (mapState: MapState): LocationDetails | null => {

    if (mapState.locations === null) {
        return null;
    }

    const portLocation = mapState.locations.filter((location) => location.x === mapState.character_position.x && location.y === mapState.character_position.y && location.is_port);

    if (portLocation.length > 0) {
        return portLocation[0]
    }

    return null;
}

/**
 * Can the player settle at their current location?
 *
 * @param component
 */
export const canSettleHere = (component: MapActions) => {

    let locations = [];

    if (component.props.locations !== null) {
        locations = component.props.locations.filter((location: LocationDetails) => {
            return location.x === component.props.character_position.x && location.y === component.props.character_position.y;
        });
    }

    const playerKingdom = component.props.player_kingdoms.filter((playerKingdom: PlayerKingdomsDetails) => {
        return playerKingdom.x_position === component.props.character_position.x && playerKingdom.y_position === component.props.character_position.y;
    });

    const enemyKingdoms = component.props.enemy_kingdoms.filter((enemyKingdom: PlayerKingdomsDetails) => {
        return enemyKingdom.x_position === component.props.character_position.x && enemyKingdom.y_position === component.props.character_position.y;
    });

    const npcKingdoms = component.props.npc_kingdoms.filter((npcKingdom: NpcKingdomsDetails) => {
        return npcKingdom.x_position === component.props.character_position.x && npcKingdom.y_position === component.props.character_position.y;
    });

    return (locations.length === 0 && playerKingdom.length === 0 && enemyKingdoms.length === 0 && npcKingdoms.length === 0);
}
