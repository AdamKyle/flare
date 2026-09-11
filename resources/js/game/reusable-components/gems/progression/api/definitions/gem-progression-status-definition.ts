import ActiveGemScrollRowDefinition from './active-gem-scroll-row-definition';
import {
  AreaGemMonsterEffectsDefinition,
  AreaGemRarityEffectsDefinition,
  AreaGemRewardEffectsDefinition,
} from '../../../api/definitions/area-gem-context-definition';

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

export interface GemProgressionActiveStatusDefinition {
  profile: GemProgressionProfileDefinition;
  global: GemProgressionGlobalDefinition;
  personal: GemProgressionPersonalDefinition;
  scroll_drop: GemProgressionScrollDropDefinition;
  active_scrolls: GemProgressionActiveScrollsDefinition;
  active_scroll_rows: ActiveGemScrollRowDefinition[];
  rolled_reward_effects: AreaGemRewardEffectsDefinition;
  effective_reward_effects: AreaGemRewardEffectsDefinition;
  rolled_monster_effects: AreaGemMonsterEffectsDefinition;
  effective_monster_effects: AreaGemMonsterEffectsDefinition;
  rolled_rarity_effects: AreaGemRarityEffectsDefinition;
  effective_rarity_effects: AreaGemRarityEffectsDefinition;
}

type GemProgressionStatusDefinition =
  | GemProgressionNoProfileStatusDefinition
  | GemProgressionActiveStatusDefinition;

export default GemProgressionStatusDefinition;
