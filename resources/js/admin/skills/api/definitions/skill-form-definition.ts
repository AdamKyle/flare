export default interface SkillFormDefinition {
  id: number;
  name: string;
  description: string;
  max_level: number;
  type: number;
  game_class_id: number | null;
  base_damage_mod_bonus_per_level: number | null;
  base_healing_mod_bonus_per_level: number | null;
  base_ac_mod_bonus_per_level: number | null;
  fight_time_out_mod_bonus_per_level: number | null;
  move_time_out_mod_bonus_per_level: number | null;
  unit_time_reduction: number | null;
  building_time_reduction: number | null;
  unit_movement_time_reduction: number | null;
  can_train: boolean | null;
  skill_bonus_per_level: number | null;
  is_locked: boolean;
  class_bonus: number | null;
}
