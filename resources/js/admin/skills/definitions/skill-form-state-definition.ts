export default interface SkillFormStateDefinition {
  name: string;
  description: string;
  max_level: string;
  type: number | null;
  game_class_id: number | null;
  base_damage_mod_bonus_per_level: string;
  base_healing_mod_bonus_per_level: string;
  base_ac_mod_bonus_per_level: string;
  fight_time_out_mod_bonus_per_level: string;
  move_time_out_mod_bonus_per_level: string;
  unit_time_reduction: string;
  building_time_reduction: string;
  unit_movement_time_reduction: string;
  can_train: boolean;
  skill_bonus_per_level: string;
  is_locked: boolean;
  class_bonus: string;
}
