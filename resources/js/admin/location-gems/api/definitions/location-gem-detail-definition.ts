import AdminRolledGemDefinition from '../../../shared/gems/api/definitions/admin-rolled-gem-definition';

export interface LocationGemRangesDefinition {
  character_xp_bonus_range: string | null;
  character_class_rank_xp_bonus_range: string | null;
  kingdom_passive_training_reduction_range: string | null;
  character_class_specialty_xp_gain_range: string | null;
  crafting_skill_bonus_range: string | null;
  gold_gain_range: string | null;
  gold_dust_gain_range: string | null;
  shards_gain_range: string | null;
  copper_coin_gain_range: string | null;
  item_drop_chance_increase_range: string | null;
  unique_item_drop_chance_increase_range: string | null;
  mythic_item_drop_chance_increase_range: string | null;
  cosmic_item_drop_chance_increase_range: string | null;
  enemy_strength_increase_range: string | null;
  enemy_healing_increase_range: string | null;
  enemy_spell_evasion_range: string | null;
  enemy_affix_resistance_range: string | null;
  enemy_entrancing_chance_range: string | null;
  enemy_devouring_light_chance_range: string | null;
  enemy_devouring_darkness_chance_range: string | null;
  enemy_ambush_chance_range: string | null;
  enemy_ambush_resistance_range: string | null;
  enemy_counter_chance_range: string | null;
  enemy_counter_resistance_range: string | null;
  enemy_quest_item_drop_chance_increase_range: string | null;
  monster_xp_increase_range: string | null;
  monster_gold_drop_increase_range: string | null;
}

export interface GeneratedGemWorldDefinition {
  id: number;
  name: string;
  generated_map_type: string | null;
  parent_map: { id: number; name: string } | null;
}

export default interface LocationGemDetailDefinition {
  id: number;
  name: string;
  description: string | null;
  game_map: { id: number; name: string };
  location: { id: number; name: string };
  ranges: LocationGemRangesDefinition;
  crafting_skills: { id: number; name: string }[];
  monster_atonement: number | null;
  monster_atonement_range: string | null;
  roll_count: number;
  rolled_gem: AdminRolledGemDefinition | null;
  roll_history: AdminRolledGemDefinition[];
  generated_gem_world: GeneratedGemWorldDefinition | null;
}
