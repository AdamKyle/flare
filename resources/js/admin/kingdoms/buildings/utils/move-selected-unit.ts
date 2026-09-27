export const moveSelectedUnit = (
  unitIds: number[],
  unitId: number,
  offset: -1 | 1
): number[] => {
  const currentIndex = unitIds.indexOf(unitId);
  const targetIndex = currentIndex + offset;

  if (currentIndex === -1 || targetIndex < 0 || targetIndex >= unitIds.length) {
    return unitIds;
  }

  const reordered = [...unitIds];

  reordered[currentIndex] = unitIds[targetIndex];
  reordered[targetIndex] = unitId;

  return reordered;
};
