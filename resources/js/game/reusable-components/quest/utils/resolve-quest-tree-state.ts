import { QuestTreeState } from '../enums/quest-tree-state';
import QuestStateResolvableDefinition from '../types/quest-state-resolvable-definition';

export const resolveQuestTreeState = (
  quest: QuestStateResolvableDefinition,
  completedQuestIds: ReadonlySet<number>
): QuestTreeState => {
  if (completedQuestIds.has(quest.id)) {
    return QuestTreeState.COMPLETED;
  }

  if (
    quest.parent_quest_id !== null &&
    !completedQuestIds.has(quest.parent_quest_id)
  ) {
    return QuestTreeState.PARENT_LOCKED;
  }

  if (
    quest.required_quest_id !== null &&
    !completedQuestIds.has(quest.required_quest_id)
  ) {
    return QuestTreeState.PREREQUISITE_LOCKED;
  }

  const hasLockedChainQuest = quest.required_quest_chain_ids.some(
    (requiredId) => !completedQuestIds.has(requiredId)
  );

  if (hasLockedChainQuest) {
    return QuestTreeState.PREREQUISITE_LOCKED;
  }

  return QuestTreeState.AVAILABLE;
};
