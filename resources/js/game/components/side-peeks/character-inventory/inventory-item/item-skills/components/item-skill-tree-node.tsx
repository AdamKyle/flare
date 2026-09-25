import React, { ReactNode } from 'react';

import ItemSkillTreeState from '../enums/item-skill-tree-state';
import ItemSkillTreeNodeData from '../types/item-skill-tree-node-data';

const ItemSkillTreeNode = ({
  skill,
  progression,
  state,
}: ItemSkillTreeNodeData): ReactNode => (
  <div className="flex h-full flex-col justify-center gap-1 p-3 text-gray-800 dark:text-gray-200">
    <span className="text-xs font-semibold uppercase">{state}</span>
    <strong className="break-words">{skill.name}</strong>
    <span className="text-sm">
      Level: {progression.current_level}/{skill.max_level}
    </span>
    {state === ItemSkillTreeState.MAXED ? (
      <span className="text-sm font-semibold">Skill is maxed</span>
    ) : (
      <span className="text-sm">
        Kills: {progression.current_kill}/{skill.total_kills_needed}
      </span>
    )}
    {state === ItemSkillTreeState.TRAINING ? (
      <span className="text-sm font-semibold">Training</span>
    ) : null}
  </div>
);

export default ItemSkillTreeNode;
