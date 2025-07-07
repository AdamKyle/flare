export default interface ItemDetails {
  id: number;
  name: string;
  affix_count: number;
  description: string;
  raw_damage: number;
  raw_ac: number | null;
  raw_healing: number | null;
  base_damage: number;
  base_ac: number;
  base_healing: number;
  base_damage_mod: number;
  base_ac_mod: number | null;
  base_healing_mod: number | null;
  str_modifier: number;
  dur_modifier: number;
  int_modifier: number;
  dex_modifier: number;
  chr_modifier: number;
  agi_modifier: number;
  focus_modifier: number;
  type: string;
  default_position: string | null;
  skill_name: string | null;
  skill_training_bonus: number | null;
  skill_bonus: number | null;
  item_prefix: string | null;
  item_suffix: string | null;
  usable: boolean;
  can_use_on_other_items: boolean;
  crafting_type: string;
  skill_level_req: number;
  skill_level_trivial: number;
  cost: number;
  base_damage_mod_bonus: number;
  base_healing_mod_bonus: number;
  base_ac_mod_bonus: number;
  fight_time_out_mod_bonus: number;
  move_time_out_mod_bonus: number;
  damages_kingdoms: boolean;
  kingdom_damage: number;
  lasts_for: number | null;
  stat_increase: boolean;
  increase_stat_by: number;
  affects_skills: string[];
  can_resurrect: boolean;
  resurrection_chance: number;
  spell_evasion: number;
  healing_reduction: number;
  affix_damage_reduction: number;
  increase_skill_bonus_by: number;
  increase_skill_training_bonus_by: number;
  is_unique: boolean;
  min_cost: number;
  holy_level: number | null;
  holy_stacks: number;
  applied_stacks: unknown[];
  holy_stack_devouring_darkness: number;
  holy_stack_stat_bonus: number;
  holy_stacks_applied: number;
  ambush_chance: number;
  ambush_resistance_chance: number;
  counter_chance: number;
  counter_resistance_chance: number;
  devouring_light: number;
  devouring_darkness: number;
  ambush_resistance: number;
  counter_resistance: number;
  is_mythic: boolean;
  is_cosmic: boolean;
  xp_bonus: number | null;
  ignores_caps: boolean;
  sockets: unknown[];
  socket_amount: number;
  item_atonements: ItemAtonements;
}

export interface ItemAtonements {
  atonements: Atonements;
  elemental_damage: ElementalDamage;
}

export interface Atonements {
  Fire: number;
  Ice: number;
  Water: number;
}

export interface ElementalDamage {
  name: string;
  amount: number;
}
