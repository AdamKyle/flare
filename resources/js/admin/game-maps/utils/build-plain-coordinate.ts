import CoordinateDefinition from '../types/coordinate-definition';

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
