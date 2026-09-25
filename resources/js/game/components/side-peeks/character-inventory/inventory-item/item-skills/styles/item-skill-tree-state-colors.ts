import ItemSkillTreeState from '../enums/item-skill-tree-state';

import TreeColor from 'ui/tree/enums/tree-color';

export const ITEM_SKILL_TREE_STATE_COLORS: Record<
  ItemSkillTreeState,
  TreeColor
> = {
  [ItemSkillTreeState.MAXED]: TreeColor.EMERALD,
  [ItemSkillTreeState.TRAINING]: TreeColor.MARIGOLD,
  [ItemSkillTreeState.LOCKED]: TreeColor.ROSE,
  [ItemSkillTreeState.AVAILABLE]: TreeColor.DANUBE,
};
