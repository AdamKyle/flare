import SkillItemContributionDefinition from './skill-item-contribution-definition';

export default interface SkillDetailDefinition {
  id: number;
  character_id: number;
  name: string;
  description: string | null;
  skill_bonus: number;
  skill_xp_bonus: number;
  skill_type: string;
  xp: number;
  xp_max: number;
  level: number;
  max_level: number;
  can_train: boolean;
  is_training: boolean;
  xp_towards: number;
  is_locked: boolean;
  unit_time_reduction: number;
  building_time_reduction: number;
  unit_movement_time_reduction: number;
  base_damage_mod: number;
  base_healing_mod: number;
  base_ac_mod: number;
  fight_timeout_mod: number;
  move_timeout_mod: number;
  class_bonus: number;
  items_affecting_skill: SkillItemContributionDefinition[];
}
