export interface ClassMasteryAttackDefinition {
  specialty_damage: number | null;
  increase_specialty_damage_per_level: number | null;
  specialty_damage_uses_damage_stat_amount: number | null;
  attack_type_required: string | null;
}

export interface ClassMasteryModifiersDefinition {
  base_damage_mod: number | null;
  base_ac_mod: number | null;
  base_healing_mod: number | null;
  base_spell_damage_mod: number | null;
  health_mod: number | null;
  base_damage_stat_increase: number | null;
}

export interface ClassMasteryEvasionAndReductionsDefinition {
  spell_evasion: number | null;
  affix_damage_reduction: number | null;
  healing_reduction: number | null;
  skill_reduction: number | null;
  resistance_reduction: number | null;
}

export default interface ClassMasteryDetailDefinition {
  id: number;
  name: string;
  description: string | null;
  game_class: {
    id: number;
    name: string;
  };
  type: 'attack' | 'passive';
  requires_class_rank_level: number;
  attack: ClassMasteryAttackDefinition;
  modifiers: ClassMasteryModifiersDefinition;
  evasion_and_reductions: ClassMasteryEvasionAndReductionsDefinition;
}
