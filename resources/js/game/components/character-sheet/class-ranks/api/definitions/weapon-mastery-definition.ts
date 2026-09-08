export default interface WeaponMasteryDefinition {
  id: number;
  character_class_rank_id: number;
  weapon_type: string;
  current_xp: number;
  required_xp: number;
  mastery_name: string;
  level: number;
  is_mastered: boolean;
}
