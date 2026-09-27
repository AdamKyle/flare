import SkillFormDefinition from '../api/definitions/skill-form-definition';
import SkillFormStateDefinition from '../definitions/skill-form-state-definition';

const toStringValue = (value: number | null): string =>
  value === null ? '' : String(value);

export const createSkillFormState = (
  skill: SkillFormDefinition | null
): SkillFormStateDefinition => {
  if (!skill) {
    return {
      name: '',
      description: '',
      max_level: '',
      type: null,
      game_class_id: null,
      base_damage_mod_bonus_per_level: '',
      base_healing_mod_bonus_per_level: '',
      base_ac_mod_bonus_per_level: '',
      fight_time_out_mod_bonus_per_level: '',
      move_time_out_mod_bonus_per_level: '',
      unit_time_reduction: '',
      building_time_reduction: '',
      unit_movement_time_reduction: '',
      can_train: true,
      skill_bonus_per_level: '',
      is_locked: false,
      class_bonus: '',
    };
  }

  return {
    name: skill.name,
    description: skill.description,
    max_level: String(skill.max_level),
    type: skill.type,
    game_class_id: skill.game_class_id,
    base_damage_mod_bonus_per_level: toStringValue(
      skill.base_damage_mod_bonus_per_level
    ),
    base_healing_mod_bonus_per_level: toStringValue(
      skill.base_healing_mod_bonus_per_level
    ),
    base_ac_mod_bonus_per_level: toStringValue(
      skill.base_ac_mod_bonus_per_level
    ),
    fight_time_out_mod_bonus_per_level: toStringValue(
      skill.fight_time_out_mod_bonus_per_level
    ),
    move_time_out_mod_bonus_per_level: toStringValue(
      skill.move_time_out_mod_bonus_per_level
    ),
    unit_time_reduction: toStringValue(skill.unit_time_reduction),
    building_time_reduction: toStringValue(skill.building_time_reduction),
    unit_movement_time_reduction: toStringValue(
      skill.unit_movement_time_reduction
    ),
    can_train: skill.can_train ?? false,
    skill_bonus_per_level: toStringValue(skill.skill_bonus_per_level),
    is_locked: skill.is_locked,
    class_bonus: toStringValue(skill.class_bonus),
  };
};
