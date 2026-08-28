import { GameMapCoordinateSize } from '../enums/game-map-coordinate-size';

const GAME_MAP_MARKER_SIZE = 24;

export interface GameMapMarkerPositionDefinition {
  left: number;
  top: number;
}

export const resolveGameMapMarkerPosition = (
  left: number,
  top: number
): GameMapMarkerPositionDefinition => {
  const cellCenterOffset = GameMapCoordinateSize.Square / 2;
  const markerCenterOffset = GAME_MAP_MARKER_SIZE / 2;

  return {
    left: left + cellCenterOffset - markerCenterOffset,
    top: top + cellCenterOffset - markerCenterOffset,
  };
};
