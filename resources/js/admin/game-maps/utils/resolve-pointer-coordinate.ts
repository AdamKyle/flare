import { findCoordinateIndex } from './find-coordinate-index';
import { GameMapCoordinateSize } from '../enums/game-map-coordinate-size';
import CoordinateDefinition from '../types/coordinate-definition';

/**
 * Resolve the map coordinate grid cell containing a map-local pointer position.
 *
 * Returns `null` when the position falls outside the returned coordinate grid.
 */
export const resolvePointerCoordinate = (
  localX: number,
  localY: number,
  xValues: number[],
  yValues: number[],
  mapWidth: number,
  mapHeight: number
): CoordinateDefinition | null => {
  const xIndex = findCoordinateIndex(localX, xValues, mapWidth);
  const yIndex = findCoordinateIndex(localY, yValues, mapHeight);

  if (xIndex === null || yIndex === null) {
    return null;
  }

  return coordinateAtIndex(xIndex, yIndex, xValues, yValues);
};

/**
 * Build the 16-by-16 coordinate grid cell rectangle for the given X/Y coordinate indexes.
 */
export const coordinateAtIndex = (
  xIndex: number,
  yIndex: number,
  xValues: number[],
  yValues: number[]
): CoordinateDefinition => {
  const left = xValues[xIndex];
  const top = yValues[yIndex];

  return {
    x_index: xIndex,
    y_index: yIndex,
    x_value: left,
    y_value: top,
    left,
    top,
    width: GameMapCoordinateSize.Square,
    height: GameMapCoordinateSize.Square,
  };
};
