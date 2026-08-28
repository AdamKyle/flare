import GameMapLocationMarkerDefinition from '../api/definitions/game-map-location-marker-definition';
import GameMapNpcMarkerDefinition from '../api/definitions/game-map-npc-marker-definition';

export default interface GameMapMarkerAtCoordinateDefinition {
  locations: GameMapLocationMarkerDefinition[];
  npcs: GameMapNpcMarkerDefinition[];
}
