import CoordinateDefinition from '../types/coordinate-definition';

/**
 * Build a CoordinateDefinition for a known X/Y map value where the pixel/index layout
 * fields are not needed by the caller (e.g. confirming a move to an already-selected
 * coordinate).
 */
export const buildPlainCoordinate = (
  x: number,
  y: number
): CoordinateDefinition => ({
  x_index: 0,
  y_index: 0,
  x_value: x,
  y_value: y,
  left: 0,
  top: 0,
  width: 0,
  height: 0,
});
