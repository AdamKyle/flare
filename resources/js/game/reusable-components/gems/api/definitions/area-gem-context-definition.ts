import GemWorldSourceDefinition from './gem-world-source-definition';

export type AreaGemContextType =
  'map' | 'location' | 'map_gem_world' | 'location_gem_world';

export interface AreaGemMonsterEffectsDefinition {
  enemy_strength_increase: number;
  enemy_healing_increase: number;
  enemy_spell_evasion: number;
  enemy_affix_resistance: number;
  enemy_entrancing_chance: number;
  enemy_devouring_light_chance: number;
  enemy_devouring_darkness_chance: number;
  enemy_ambush_chance: number;
  enemy_ambush_resistance: number;
  enemy_counter_chance: number;
  enemy_counter_resistance: number;
  atonement_type: number | null;
  atonement_amount: number | null;
}

export interface AreaGemRewardEffectsDefinition {
  character_xp_bonus: number;
  character_class_rank_xp_bonus: number;
  kingdom_passive_training_reduction: number;
  gold_gain: number;
  gold_dust_gain: number;
  shards_gain: number;
  copper_coin_gain: number;
  character_class_specialty_xp_gain: number;
  item_drop_chance_increase: number;
  enemy_quest_item_drop_chance_increase: number;
  monster_xp_increase: number;
  monster_gold_drop_increase: number;
}

export interface AreaGemCraftingSkillBonusDefinition {
  id: number;
  name: string;
  bonus: number;
}

export interface AreaGemRarityEffectsDefinition {
  unique: number;
  mythic: number;
  cosmic: number;
}

export default interface AreaGemContextDefinition {
  type: AreaGemContextType;
  label: string;
  game_map: { id: number; name: string } | null;
  location: { id: number; name: string } | null;
  rules: string[];
  sources: GemWorldSourceDefinition[];
  character_power_reduction: number;
  monster_effects: AreaGemMonsterEffectsDefinition;
  reward_effects: AreaGemRewardEffectsDefinition;
  crafting_skill_bonuses: AreaGemCraftingSkillBonusDefinition[];
  rarity_effects: AreaGemRarityEffectsDefinition;
}
