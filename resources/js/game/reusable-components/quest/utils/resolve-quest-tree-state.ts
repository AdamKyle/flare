import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import { QuestTreeState } from '../enums/quest-tree-state';

/**
 * Resolve a Quest tree node's structural state from the current player's completed Quest ids.
 *
 * Order matters: completion always wins, then a locked parent, then any locked prerequisite
 * (direct required Quest or required Quest chain), otherwise the Quest is available.
 */
export const resolveQuestTreeState = (
  quest: QuestTreeNodeDefinition,
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
