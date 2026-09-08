import GameMapLocationMarkerDefinition from '../api/definitions/game-map-location-marker-definition';
import GameMapNpcMarkerDefinition from '../api/definitions/game-map-npc-marker-definition';
import GameMapMarkerAtCoordinateDefinition from '../types/game-map-marker-at-coordinate-definition';

export const findMarkersAtCoordinate = (
  x: number,
  y: number,
  locations: GameMapLocationMarkerDefinition[],
  npcs: GameMapNpcMarkerDefinition[]
): GameMapMarkerAtCoordinateDefinition => ({
  locations: locations.filter(
    (location) => location.x === x && location.y === y
  ),
  npcs: npcs.filter((npc) => npc.x_position === x && npc.y_position === y),
});
