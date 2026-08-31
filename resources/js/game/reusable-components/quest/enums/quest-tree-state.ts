export enum QuestTreeState {
  COMPLETED = 'completed',
  PARENT_LOCKED = 'parent_locked',
  PREREQUISITE_LOCKED = 'prerequisite_locked',
  AVAILABLE = 'available',
}

export const QUEST_TREE_STATE_LABELS: Record<QuestTreeState, string> = {
  [QuestTreeState.COMPLETED]: 'Completed',
  [QuestTreeState.PARENT_LOCKED]: 'Locked (parent Quest incomplete)',
  [QuestTreeState.PREREQUISITE_LOCKED]: 'Locked (prerequisite incomplete)',
  [QuestTreeState.AVAILABLE]: 'Available',
};

export const QUEST_TREE_STATE_ICON: Record<QuestTreeState, string> = {
  [QuestTreeState.COMPLETED]: '✓',
  [QuestTreeState.PARENT_LOCKED]: '🔒',
  [QuestTreeState.PREREQUISITE_LOCKED]: '⚠',
  [QuestTreeState.AVAILABLE]: '●',
};
