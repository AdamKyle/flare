import { QuestKind } from '../enums/quest-kind';

/**
 * Build a deterministic frontend-only identity for a Quest tree request/result
 * pair, so a renderer can confirm a response belongs to the exact currently
 * selected `{Game Map, Quest kind}` query before displaying it. Never sent to
 * the backend and never persisted.
 *
 * @param  mapId  Selected Game Map id, or `null` when no Map is selected.
 * @param  kind  Selected Quest kind, or `null` when no kind filter applies.
 * @return  The query identity string, or `null` when no Map is selected.
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
