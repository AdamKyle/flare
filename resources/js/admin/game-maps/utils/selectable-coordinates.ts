import { GameMapCoordinateSize } from '../enums/game-map-coordinate-size';

/**
 * Keep only coordinates whose full 16-by-16 cell fits inside the map bound.
 */
export const selectableCoordinateValues = (
  values: number[],
  axisUpperBound: number
): number[] =>
  values.filter(
    (value) => value + GameMapCoordinateSize.Square <= axisUpperBound
  );
