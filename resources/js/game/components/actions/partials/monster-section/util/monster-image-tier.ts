export const getImageTierByIndex = (
  monsterIndex: number,
  totalMonsters: number,
  imageCount: number
): number => {
  if (imageCount <= 1) {
    return 0;
  }

  if (totalMonsters <= 0) {
    return 0;
  }

  const clampedIndex = Math.max(0, Math.min(totalMonsters - 1, monsterIndex));
  const bucketSize = Math.ceil(totalMonsters / imageCount);
  const rawTier = Math.floor(clampedIndex / bucketSize);

  return Math.max(0, Math.min(imageCount - 1, rawTier));
};
