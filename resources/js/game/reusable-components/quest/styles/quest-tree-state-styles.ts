import { QuestTreeState } from '../enums/quest-tree-state';

import TreeColor from 'ui/tree/enums/tree-color';

export const QUEST_TREE_STATE_TREE_COLOR: Record<QuestTreeState, TreeColor> = {
  [QuestTreeState.COMPLETED]: TreeColor.EMERALD,
  [QuestTreeState.PARENT_LOCKED]: TreeColor.ROSE,
  [QuestTreeState.PREREQUISITE_LOCKED]: TreeColor.MARIGOLD,
  [QuestTreeState.AVAILABLE]: TreeColor.DANUBE,
};

export const QUEST_TREE_STATE_TEXT_STYLES: Record<QuestTreeState, string> = {
  [QuestTreeState.COMPLETED]: 'text-emerald-700 dark:text-emerald-400',
  [QuestTreeState.PARENT_LOCKED]: 'text-rose-700 dark:text-rose-400',
  [QuestTreeState.PREREQUISITE_LOCKED]:
    'text-marigold-700 dark:text-marigold-400',
  [QuestTreeState.AVAILABLE]: 'text-danube-700 dark:text-danube-400',
};

export const QUEST_TREE_STATE_BORDER_STYLES: Record<QuestTreeState, string> = {
  [QuestTreeState.COMPLETED]: 'border-emerald-700 dark:border-emerald-500',
  [QuestTreeState.PARENT_LOCKED]: 'border-rose-700 dark:border-rose-500',
  [QuestTreeState.PREREQUISITE_LOCKED]:
    'border-marigold-800 dark:border-marigold-500',
  [QuestTreeState.AVAILABLE]: 'border-danube-800 dark:border-danube-500',
};
