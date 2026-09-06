export default interface ClassMasteryFormStateDefinition {
  game_class_id: number | null;
  name: string;
  description: string;
  requires_class_rank_level: string;
  specialty_damage: string;
  increase_specialty_damage_per_level: string;
  specialty_damage_uses_damage_stat_amount: string;
  attack_type_required: string | null;
  base_damage_mod: string;
  base_ac_mod: string;
  base_healing_mod: string;
  base_spell_damage_mod: string;
  health_mod: string;
  base_damage_stat_increase: string;
  spell_evasion: string;
  affix_damage_reduction: string;
  healing_reduction: string;
  skill_reduction: string;
  resistance_reduction: string;
}
