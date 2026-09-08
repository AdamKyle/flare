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
