/**
 * Clamp a single-axis map translation so the map never scrolls past its own bounds,
 * centering the map on that axis when it is smaller than the viewport.
 */
export const clampMapAxis = (
  value: number,
  mapSize: number,
  viewportSize: number
): number => {
  if (mapSize <= viewportSize) {
    return (viewportSize - mapSize) / 2;
  }

  const min = viewportSize - mapSize;

  return Math.min(0, Math.max(min, value));
};
