import { QuestTreeState } from '../enums/quest-tree-state';

interface QuestTreeNodeLevelStyles {
  card: string;
  name: string;
}

/**
 * Compact desktop-tree state color: text/icon only, no pill background, so
 * color stays a supplementary signal alongside the icon and compact label
 * instead of dominating an already-narrow node.
 */
export const QUEST_TREE_NODE_STATE_TEXT_STYLES: Record<QuestTreeState, string> =
  {
    [QuestTreeState.COMPLETED]: 'text-green-700 dark:text-green-700',
    [QuestTreeState.PARENT_LOCKED]: 'text-red-700 dark:text-red-700',
    [QuestTreeState.PREREQUISITE_LOCKED]:
      'text-marigold-700 dark:text-marigold-700',
    [QuestTreeState.AVAILABLE]: 'text-danube-700 dark:text-danube-700',
  };

/**
 * Canonical Quest state border language, preserved from 1.0: red for
 * `PARENT_LOCKED` ("Cannot complete yet"), Danube for `AVAILABLE`, green
 * for `COMPLETED` ("Done"), and marigold for `PREREQUISITE_LOCKED` ("Needs
 * quests"). This border is the primary structural-state signal on every
 * stateful desktop tree node and Quest card; color is never the only
 * signal because the icon and visible/accessible label remain alongside
 * it.
 */
export const QUEST_TREE_STATE_BORDER_STYLES: Record<QuestTreeState, string> = {
  [QuestTreeState.COMPLETED]: 'border-green-800 dark:border-green-500',
  [QuestTreeState.PARENT_LOCKED]: 'border-red-800 dark:border-red-500',
  [QuestTreeState.PREREQUISITE_LOCKED]:
    'border-marigold-800 dark:border-marigold-500',
  [QuestTreeState.AVAILABLE]: 'border-danube-800 dark:border-danube-500',
};

/**
 * Level-aware desktop tree card density: background, shadow, and padding
 * only. Width is owned by the node's layout wrapper — the root centers a
 * compact fixed max-width card, while every major branch and deeper
 * descendant fills its bounded branch lane (`max-w-sm`, see
 * `QuestTreeDesktopNode`) rather than being capped by its own max-width, so
 * a readable Quest name is never crushed into a narrow column. Border
 * width/color is owned separately (`border-2` plus
 * `QUEST_TREE_STATE_BORDER_STYLES`) so the structural Quest state border
 * stays uniformly visible at every depth. The root visually anchors the
 * hierarchy with a stronger shadow/tone and the largest Quest name; major
 * branches and deeper descendants use the standard Glacier card treatment.
 *
 * @param  depth  The node's zero-based tree depth (root is `0`).
 * @return  The card and Quest-name class strings for that depth.
 */
export const resolveQuestTreeNodeLevelStyles = (
  depth: number
): QuestTreeNodeLevelStyles => {
  if (depth === 0) {
    return {
      card: 'shadow-md px-4 py-3 max-w-[16rem] bg-glacier-200 hover:bg-glacier-300 dark:bg-glacier-200 dark:hover:bg-glacier-300',
      name: 'text-sm font-semibold',
    };
  }

  if (depth === 1) {
    return {
      card: 'shadow-sm px-3 py-2.5 w-full bg-glacier-100 hover:bg-glacier-200 dark:bg-glacier-100 dark:hover:bg-glacier-200',
      name: 'text-sm font-medium',
    };
  }

  return {
    card: 'shadow-sm px-2.5 py-2 w-full bg-glacier-100 hover:bg-glacier-200 dark:bg-glacier-100 dark:hover:bg-glacier-200',
    name: 'text-xs font-medium sm:text-sm',
  };
};

/**
 * Extra breathing room between a node and its own children: the root gets a
 * slightly larger gap to anchor the hierarchy before branching begins, and
 * deeper generations use a smaller, consistent gap.
 *
 * @param  depth  The parent node's zero-based tree depth (root is `0`).
 * @return  The margin-top class for that node's children region.
 */
export const resolveQuestTreeChildrenSpacingClass = (depth: number): string =>
  depth === 0 ? 'mt-6' : 'mt-4';
