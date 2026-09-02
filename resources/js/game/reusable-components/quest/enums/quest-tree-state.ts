export enum QuestTreeState {
  COMPLETED = 'completed',
  PARENT_LOCKED = 'parent_locked',
  PREREQUISITE_LOCKED = 'prerequisite_locked',
  AVAILABLE = 'available',
}

/**
 * Full accessible state description, used for the screen-reader-only
 * explanatory text. Never shown as the primary visible badge text.
 */
export const QUEST_TREE_STATE_LABELS: Record<QuestTreeState, string> = {
  [QuestTreeState.COMPLETED]: 'Completed',
  [QuestTreeState.PARENT_LOCKED]: 'Locked (parent Quest incomplete)',
  [QuestTreeState.PREREQUISITE_LOCKED]: 'Locked (prerequisite incomplete)',
  [QuestTreeState.AVAILABLE]: 'Available',
};

/**
 * Short visible badge text. The full explanation lives in
 * `QUEST_TREE_STATE_LABELS`, surfaced through the accessible name/sr-only
 * text instead of the primary visible label.
 */
export const QUEST_TREE_STATE_SHORT_LABELS: Record<QuestTreeState, string> = {
  [QuestTreeState.COMPLETED]: 'Done',
  [QuestTreeState.PARENT_LOCKED]: 'Cannot complete yet',
  [QuestTreeState.PREREQUISITE_LOCKED]: 'Needs quests',
  [QuestTreeState.AVAILABLE]: 'Available',
};

export const QUEST_TREE_STATE_ICON: Record<QuestTreeState, string> = {
  [QuestTreeState.COMPLETED]: '✓',
  [QuestTreeState.PARENT_LOCKED]: '🔒',
  [QuestTreeState.PREREQUISITE_LOCKED]: '⚠',
  [QuestTreeState.AVAILABLE]: '●',
};
