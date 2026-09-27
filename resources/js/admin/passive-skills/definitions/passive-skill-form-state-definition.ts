export default interface PassiveSkillFormStateDefinition {
  name: string;
  description: string;
  effect_type: number | null;
  max_level: string;
  hours_per_level: string;
  is_locked: boolean;
  is_parent: boolean;
  bonus_per_level: string;
  resource_bonus_per_level: string;
  capital_city_building_request_travel_time_reduction: string;
  capital_city_unit_request_travel_time_reduction: string;
  resource_request_time_reduction: string;
  parent_skill_id: number | null;
  unlocks_at_level: string;
}
