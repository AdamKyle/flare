import { GameMapCoordinateSize } from '../enums/game-map-coordinate-size';

/**
 * Coordinates are half-open 16px squares; positions outside a complete cell return null.
 */
export const findCoordinateIndex = (
  position: number,
  values: number[],
  upperBound: number
): number | null => {
  if (values.length === 0 || position < values[0] || position >= upperBound) {
    return null;
  }

  for (let index = 0; index < values.length; index += 1) {
    const left = values[index];
    const right = left + GameMapCoordinateSize.Square;

    if (right > upperBound) {
      return null;
    }

    if (position >= left && position < right) {
      return index;
    }
  }

  return null;
};
