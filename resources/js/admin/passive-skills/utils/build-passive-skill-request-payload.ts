import PassiveSkillRequestDefinition from '../api/definitions/passive-skill-request-definition';
import PassiveSkillFormStateDefinition from '../definitions/passive-skill-form-state-definition';

const toNumberOrNull = (value: string): number | null =>
  value.trim() === '' ? null : Number(value);

export const buildPassiveSkillRequestPayload = (
  state: PassiveSkillFormStateDefinition
): PassiveSkillRequestDefinition => ({
  name: state.name.trim(),
  description: state.description,
  max_level: Number(state.max_level),
  effect_type: state.effect_type ?? 0,
  bonus_per_level: toNumberOrNull(state.bonus_per_level),
  resource_bonus_per_level: toNumberOrNull(state.resource_bonus_per_level),
  capital_city_building_request_travel_time_reduction: toNumberOrNull(
    state.capital_city_building_request_travel_time_reduction
  ),
  capital_city_unit_request_travel_time_reduction: toNumberOrNull(
    state.capital_city_unit_request_travel_time_reduction
  ),
  resource_request_time_reduction: toNumberOrNull(
    state.resource_request_time_reduction
  ),
  parent_skill_id: state.parent_skill_id,
  unlocks_at_level: toNumberOrNull(state.unlocks_at_level),
  hours_per_level: Number(state.hours_per_level),
  is_locked: state.is_locked,
  is_parent: state.is_parent,
});
