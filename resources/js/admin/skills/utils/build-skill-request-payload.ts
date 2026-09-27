import SkillRequestDefinition from '../api/definitions/skill-request-definition';
import SkillFormStateDefinition from '../definitions/skill-form-state-definition';

const toNumberOrNull = (value: string): number | null =>
  value.trim() === '' ? null : Number(value);

export const buildSkillRequestPayload = (
  state: SkillFormStateDefinition
): SkillRequestDefinition => ({
  name: state.name.trim(),
  description: state.description,
  max_level: Number(state.max_level),
  type: state.type ?? 0,
  game_class_id: state.game_class_id,
  base_damage_mod_bonus_per_level: toNumberOrNull(
    state.base_damage_mod_bonus_per_level
  ),
  base_healing_mod_bonus_per_level: toNumberOrNull(
    state.base_healing_mod_bonus_per_level
  ),
  base_ac_mod_bonus_per_level: toNumberOrNull(
    state.base_ac_mod_bonus_per_level
  ),
  fight_time_out_mod_bonus_per_level: toNumberOrNull(
    state.fight_time_out_mod_bonus_per_level
  ),
  move_time_out_mod_bonus_per_level: toNumberOrNull(
    state.move_time_out_mod_bonus_per_level
  ),
  unit_time_reduction: toNumberOrNull(state.unit_time_reduction),
  building_time_reduction: toNumberOrNull(state.building_time_reduction),
  unit_movement_time_reduction: toNumberOrNull(
    state.unit_movement_time_reduction
  ),
  can_train: state.can_train,
  skill_bonus_per_level: toNumberOrNull(state.skill_bonus_per_level),
  is_locked: state.is_locked,
  class_bonus: toNumberOrNull(state.class_bonus),
});
