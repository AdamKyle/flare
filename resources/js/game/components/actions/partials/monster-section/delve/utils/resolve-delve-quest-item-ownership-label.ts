import DelveQuestItemDefinition from '../api/definitions/delve-quest-item-definition';

export const resolveDelveQuestItemOwnershipLabel = (
  questItem: DelveQuestItemDefinition
): string => {
  if (questItem.have) {
    return 'In your inventory';
  }

  if (questItem.had) {
    return 'Already used for a completed quest';
  }

  return 'Not yet found';
};
