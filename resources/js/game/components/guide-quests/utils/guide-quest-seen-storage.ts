const guideQuestSeenKey = (characterId: number, guideQuestId: number): string =>
  `guide-quest-seen:${characterId}:${guideQuestId}`;

/**
 * Unreadable storage counts as unseen so the Guide Quest can still auto-open.
 */
export const hasSeenGuideQuest = (
  characterId: number,
  guideQuestId: number
): boolean => {
  try {
    return (
      window.localStorage.getItem(
        guideQuestSeenKey(characterId, guideQuestId)
      ) === '1'
    );
  } catch {
    return false;
  }
};

/**
 * Unwritable storage is ignored; the Guide Quest may auto-open again later.
 */
export const markGuideQuestSeen = (
  characterId: number,
  guideQuestId: number
): void => {
  try {
    window.localStorage.setItem(
      guideQuestSeenKey(characterId, guideQuestId),
      '1'
    );
  } catch {
    return;
  }
};
