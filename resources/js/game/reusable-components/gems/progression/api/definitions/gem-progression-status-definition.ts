export interface GemFieldProgressionBreakdownDefinition {
  field: string;
  base: number;
  global: number;
  personal: number;
  global_effective: number;
  effective: number;
}

export interface GemProgressionProfileDefinition {
  type: string;
  id: number;
  generated_game_map_id: number;
  generated_game_map_name: string;
}

export interface GemProgressionGlobalDefinition {
  level: number;
  xp: number;
  next_level_xp: number;
  max_level: number;
}

export interface GemProgressionNextUnlockDefinition {
  level: number;
  description: string;
}

export interface GemProgressionPersonalDefinition {
  level: number;
  xp: number;
  next_level_xp: number;
  max_level: number;
  negative_bonus: number;
  unique_chance_bonus: number;
  mythic_chance_bonus: number;
  cosmic_chance_bonus: number;
  enhanced_equipment_chance: number;
  enhanced_equipment_unlocked: boolean;
  next_unlock: GemProgressionNextUnlockDefinition | null;
}

export interface GemProgressionScrollDropDefinition {
  eligible: boolean;
  chance: number;
}

export interface GemProgressionActiveScrollsDefinition {
  count: number;
  total_primary_bonus: number;
  cap: number;
  remaining_capacity: number;
  xp_bonus: number;
  gold_bonus: number;
  copper_coins_bonus: number;
  gold_dust_bonus: number;
  shards_bonus: number;
  item_bonus: number;
}

export interface GemProgressionNoProfileStatusDefinition {
  profile: null;
}

export interface GemProgressionCompactStatusDefinition {
  profile: GemProgressionProfileDefinition;
  global: GemProgressionGlobalDefinition;
  personal: GemProgressionPersonalDefinition;
  scroll_drop: GemProgressionScrollDropDefinition;
  active_scrolls: GemProgressionActiveScrollsDefinition;
  currently_in_this_gem_world: boolean;
}

export interface GemProgressionActiveStatusDefinition {
  profile: GemProgressionProfileDefinition;
  global: GemProgressionGlobalDefinition;
  personal: GemProgressionPersonalDefinition;
  scroll_drop: GemProgressionScrollDropDefinition;
  active_scrolls: GemProgressionActiveScrollsDefinition;
  reward_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
  rarity_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
}

type GemProgressionStatusDefinition =
  | GemProgressionNoProfileStatusDefinition
  | GemProgressionActiveStatusDefinition;

export default GemProgressionStatusDefinition;
