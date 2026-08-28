import { GameMapCoordinateSize } from '../enums/game-map-coordinate-size';

/**
 * Filter a coordinate axis down to the values whose full 16-by-16 square fits within the
 * map's pixel bound on that axis, so out-of-bounds coordinates are never selectable by
 * pointer or keyboard.
 */
export const selectableCoordinateValues = (
  values: number[],
  axisUpperBound: number
): number[] =>
  values.filter(
    (value) => value + GameMapCoordinateSize.Square <= axisUpperBound
  );
