export default interface PassiveSkillRequestDefinition {
  name: string;
  description: string;
  max_level: number;
  effect_type: number;
  bonus_per_level: number | null;
  resource_bonus_per_level: number | null;
  capital_city_building_request_travel_time_reduction: number | null;
  capital_city_unit_request_travel_time_reduction: number | null;
  resource_request_time_reduction: number | null;
  parent_skill_id: number | null;
  unlocks_at_level: number | null;
  hours_per_level: number;
  is_locked: boolean;
  is_parent: boolean;
}
