export default interface ClassMasteryFormDefinition {
  id: number;
  game_class_id: number;
  name: string;
  description: string | null;
  requires_class_rank_level: number;
  specialty_damage: number | null;
  increase_specialty_damage_per_level: number | null;
  specialty_damage_uses_damage_stat_amount: number | null;
  attack_type_required: string | null;
  base_damage_mod: number | null;
  base_ac_mod: number | null;
  base_healing_mod: number | null;
  base_spell_damage_mod: number | null;
  health_mod: number | null;
  base_damage_stat_increase: number | null;
  spell_evasion: number | null;
  affix_damage_reduction: number | null;
  healing_reduction: number | null;
  skill_reduction: number | null;
  resistance_reduction: number | null;
}
