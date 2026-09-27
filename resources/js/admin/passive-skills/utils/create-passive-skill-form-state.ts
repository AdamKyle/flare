import PassiveSkillFormDefinition from '../api/definitions/passive-skill-form-definition';
import PassiveSkillFormStateDefinition from '../definitions/passive-skill-form-state-definition';

const toStringValue = (value: number | null): string =>
  value === null ? '' : String(value);

export const createPassiveSkillFormState = (
  passiveSkill: PassiveSkillFormDefinition | null
): PassiveSkillFormStateDefinition => {
  if (!passiveSkill) {
    return {
      name: '',
      description: '',
      effect_type: null,
      max_level: '',
      hours_per_level: '',
      is_locked: false,
      is_parent: false,
      bonus_per_level: '',
      resource_bonus_per_level: '',
      capital_city_building_request_travel_time_reduction: '',
      capital_city_unit_request_travel_time_reduction: '',
      resource_request_time_reduction: '',
      parent_skill_id: null,
      unlocks_at_level: '',
    };
  }

  return {
    name: passiveSkill.name,
    description: passiveSkill.description,
    effect_type: passiveSkill.effect_type,
    max_level: String(passiveSkill.max_level),
    hours_per_level: String(passiveSkill.hours_per_level),
    is_locked: passiveSkill.is_locked,
    is_parent: passiveSkill.is_parent,
    bonus_per_level: toStringValue(passiveSkill.bonus_per_level),
    resource_bonus_per_level: toStringValue(
      passiveSkill.resource_bonus_per_level
    ),
    capital_city_building_request_travel_time_reduction: toStringValue(
      passiveSkill.capital_city_building_request_travel_time_reduction
    ),
    capital_city_unit_request_travel_time_reduction: toStringValue(
      passiveSkill.capital_city_unit_request_travel_time_reduction
    ),
    resource_request_time_reduction: toStringValue(
      passiveSkill.resource_request_time_reduction
    ),
    parent_skill_id: passiveSkill.parent_skill_id,
    unlocks_at_level: toStringValue(passiveSkill.unlocks_at_level),
  };
};
