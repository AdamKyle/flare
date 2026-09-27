import React, { ReactNode, useMemo } from 'react';

import { passiveSkillCardSecondaryTextStyles } from '../styles/passive-skill-card-styles';
import PassiveSkillTreeNodeData from '../types/passive-skill-tree-node-data';
import PassiveSkillTreeProps from '../types/passive-skill-tree-props';
import { buildPassiveSkillTreeData } from '../utils/build-passive-skill-tree-data';

import TreeNodeDefinition from 'ui/tree/definitions/tree-node-definition';
import TreeMobileMode from 'ui/tree/enums/tree-mobile-mode';
import Tree from 'ui/tree/tree';

const PassiveSkillTree = ({
  passive_skills: passiveSkills,
  effect_label: effectLabel,
  on_activate: onActivate,
}: PassiveSkillTreeProps): ReactNode => {
  const treeData = useMemo(
    () => buildPassiveSkillTreeData(passiveSkills, effectLabel),
    [passiveSkills, effectLabel]
  );

  const handleActivate = (
    node: TreeNodeDefinition<PassiveSkillTreeNodeData>
  ): void => {
    onActivate?.(node.data.passive_skill.id);
  };

  const renderNode = (
    node: TreeNodeDefinition<PassiveSkillTreeNodeData>
  ): ReactNode => {
    const passiveSkill = node.data.passive_skill;

    return (
      <span className="bg-taupe-gray-100 text-taupe-gray-900 dark:bg-taupe-gray-950 dark:text-taupe-gray-100 flex h-full w-full items-start gap-3 p-4">
        <i className="ra ra-book text-2xl" aria-hidden="true" />
        <span className="flex min-w-0 flex-1 flex-col gap-1">
          <span className="text-sm font-semibold break-words">
            {passiveSkill.name}
          </span>
          <span className={`text-xs ${passiveSkillCardSecondaryTextStyles()}`}>
            {node.data.effect_label}
          </span>
          {passiveSkill.unlocks_at_level && (
            <span
              className={`text-xs ${passiveSkillCardSecondaryTextStyles()}`}
            >
              Unlocks At Level: {passiveSkill.unlocks_at_level}
            </span>
          )}
        </span>
      </span>
    );
  };

  return (
    <Tree<PassiveSkillTreeNodeData>
      nodes={treeData.nodes}
      branches={treeData.branches}
      render_node={renderNode}
      render_list_node={renderNode}
      on_node_activate={onActivate ? handleActivate : undefined}
      accessibility_label="Passive Skill tree"
      mobile_mode={TreeMobileMode.ONLY_WHATS_AVAILABLE}
      empty_state={
        <p className="text-sm text-gray-600 dark:text-gray-400">
          No Passive Skills are available.
        </p>
      }
      available_empty_state={
        <p className="text-sm text-gray-600 dark:text-gray-400">
          No Passive Skills are available.
        </p>
      }
      default_zoom={0.8}
    />
  );
};

export default PassiveSkillTree;
