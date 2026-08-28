import { GameMapCoordinateSize } from '../enums/game-map-coordinate-size';

/**
 * Find the index of the coordinate whose 16-by-16 square contains the given position.
 *
 * Coordinates are contiguous, half-open 16px squares: `[values[index], values[index] + 16)`.
 * A position before the first coordinate, at or past the map's pixel bound, or landing in a
 * square that would extend past the map's pixel bound is outside the selectable grid and
 * returns `null` rather than rounding to the nearest or final coordinate.
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
