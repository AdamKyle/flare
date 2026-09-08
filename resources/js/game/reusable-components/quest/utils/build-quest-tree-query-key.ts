import { QuestKind } from '../enums/quest-kind';

/**
 * Key results to the selected Map/kind so stale responses are not rendered for a new selection.
 */
export const buildQuestTreeQueryKey = (
  mapId: number | null,
  kind: QuestKind | null
): string | null => {
  if (mapId === null) {
    return null;
  }

  return `${mapId}:${kind ?? 'all'}`;
};
