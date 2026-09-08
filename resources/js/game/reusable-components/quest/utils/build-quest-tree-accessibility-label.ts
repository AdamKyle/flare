export const buildQuestTreeAccessibilityLabel = (
  gameMapName: string | null,
  raidName: string | null = null
): string => {
  if (gameMapName && raidName) {
    return `${gameMapName} — ${raidName} Quest Tree`;
  }

  if (gameMapName) {
    return `${gameMapName} Quest Tree`;
  }

  if (raidName) {
    return `${raidName} Quest Tree`;
  }

  return 'Quest Tree';
};
