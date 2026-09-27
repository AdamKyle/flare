import PassiveSkillTreeDataDefinition from '../definitions/passive-skill-tree-data-definition';

export default interface PassiveSkillTreeProps {
  passive_skills: PassiveSkillTreeDataDefinition[];
  effect_label: (effectType: number) => string;
  on_activate?: (passiveSkillId: number) => void;
}
