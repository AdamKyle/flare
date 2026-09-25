import { resolveItemSkillState } from './resolve-item-skill-state';
import ItemSkillDefinition from '../../../../../../api-definitions/items/item-skill-definition';
import ItemSkillProgressionDefinition from '../../../../../../api-definitions/items/item-skill-progression-definition';
import ItemSkillTreeState from '../enums/item-skill-tree-state';
import { ITEM_SKILL_TREE_STATE_COLORS } from '../styles/item-skill-tree-state-colors';
import ItemSkillTreeData from '../types/item-skill-tree-data';

import TreeColor from 'ui/tree/enums/tree-color';

export const buildItemSkillTreeData = (
  skills: ItemSkillDefinition[],
  progressions: ItemSkillProgressionDefinition[]
): ItemSkillTreeData => {
  const nodes: ItemSkillTreeData['nodes'] = [];
  const branches: ItemSkillTreeData['branches'] = [];
  let isIncomplete = false;

  const visit = (skill: ItemSkillDefinition, parentId: number | null): void => {
    const progression = progressions.find(
      (candidate) => candidate.item_skill_id === skill.id
    );

    if (!progression) {
      isIncomplete = true;
      skill.children.forEach((child) => visit(child, null));

      return;
    }

    const state = resolveItemSkillState(skill, skills, progressions);
    const childCount = skill.children.length;
    const progress =
      state === ItemSkillTreeState.MAXED
        ? 'Skill is maxed.'
        : `Kills ${progression.current_kill} of ${skill.total_kills_needed}.`;

    nodes.push({
      id: String(skill.id),
      label: skill.name,
      data: { skill, progression, state },
      color: ITEM_SKILL_TREE_STATE_COLORS[state],
      is_available: state !== ItemSkillTreeState.LOCKED,
      accessibility_label: `${skill.name}. ${state}. Level ${progression.current_level} of ${skill.max_level}. ${progress} ${childCount} child${childCount === 1 ? '' : 'ren'}.`,
      height: 132,
    });

    if (parentId !== null) {
      branches.push({
        id: `${parentId}-${skill.id}`,
        source_id: String(parentId),
        target_id: String(skill.id),
        color: TreeColor.DANUBE,
      });
    }

    skill.children.forEach((child) => visit(child, skill.id));
  };

  skills.forEach((skill) => visit(skill, null));

  return { nodes, branches, is_incomplete: isIncomplete };
};
