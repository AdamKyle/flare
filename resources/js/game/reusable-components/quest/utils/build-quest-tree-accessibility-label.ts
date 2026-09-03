/**
 * Build a factual, contextual accessibility label for one Quest Tree
 * presentation: the selected Game Map name and, for a Raid Tree, the Raid
 * name. Never invents a Map/Raid name — when the selected Map's identity is
 * unavailable, the smallest truthful fallback is used instead.
 *
 * @param  gameMapName  Factual selected Game Map name, when known.
 * @param  raidName  Factual Raid name, when this Tree is a Raid Tree.
 * @return  Contextual Quest Tree accessibility label.
 */
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
