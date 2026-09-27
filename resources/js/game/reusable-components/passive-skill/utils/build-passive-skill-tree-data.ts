import PassiveSkillTreeDataDefinition from '../definitions/passive-skill-tree-data-definition';
import PassiveSkillTreeData from '../types/passive-skill-tree-data';

import TreeColor from 'ui/tree/enums/tree-color';

export const buildPassiveSkillTreeData = (
  passiveSkills: PassiveSkillTreeDataDefinition[],
  effectLabel: (effectType: number) => string
): PassiveSkillTreeData => ({
  nodes: passiveSkills.map((passiveSkill) => {
    const label = effectLabel(passiveSkill.effect_type);

    return {
      id: String(passiveSkill.id),
      label: passiveSkill.name,
      data: { passive_skill: passiveSkill, effect_label: label },
      color: TreeColor.MARIGOLD,
      is_available: true,
      accessibility_label: `${passiveSkill.name}. Effect: ${label}.${passiveSkill.unlocks_at_level ? ` Unlocks at level ${passiveSkill.unlocks_at_level}.` : ''}`,
    };
  }),
  branches: passiveSkills
    .filter((passiveSkill) => passiveSkill.parent_id !== null)
    .map((passiveSkill) => ({
      id: `${passiveSkill.parent_id}-${passiveSkill.id}`,
      source_id: String(passiveSkill.parent_id),
      target_id: String(passiveSkill.id),
      color: TreeColor.MARIGOLD,
    })),
});
