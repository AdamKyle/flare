import { GameMapRequiredQuestItemDefinition } from '../api/definitions/game-map-editor-definition';
import { GameMapCopy } from '../enums/game-map-copy';

/**
 * Resolve the copy describing a Game Map's required quest Item and its acquisition Quest.
 */
export const resolveRequiredQuestItemCopy = (
  requiredQuestItem: GameMapRequiredQuestItemDefinition | null
): string => {
  if (!requiredQuestItem) {
    return GameMapCopy.NoRequiredQuestItem;
  }

  if (!requiredQuestItem.quest) {
    return GameMapCopy.NoAcquisitionQuest;
  }

  return `Obtain this item by completing ${requiredQuestItem.quest.name}.`;
};
