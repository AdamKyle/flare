import AdminRolledGemDefinition from '../../shared/gems/api/definitions/admin-rolled-gem-definition';
import { MapGemRangesDefinition } from '../api/definitions/map-gem-detail-definition';

export interface MapGemRangeDisplayField {
  range_field: keyof MapGemRangesDefinition;
  rolled_field: keyof AdminRolledGemDefinition;
  label: string;
}

export interface MapGemRangeDisplayGroup {
  title: string;
  fields: MapGemRangeDisplayField[];
}

export const MAP_GEM_RANGE_DISPLAY_GROUPS: MapGemRangeDisplayGroup[] = [
  {
    title: 'Player Rewards',
    fields: [
      {
        range_field: 'character_xp_bonus_range',
        rolled_field: 'character_xp_bonus',
        label: 'Character XP Bonus',
      },
      {
        range_field: 'character_class_rank_xp_bonus_range',
        rolled_field: 'character_class_rank_xp_bonus',
        label: 'Character Class Rank XP Bonus',
      },
      {
        range_field: 'kingdom_passive_training_reduction_range',
        rolled_field: 'kingdom_passive_training_reduction',
        label: 'Kingdom Passive Training Reduction',
      },
      {
        range_field: 'character_class_specialty_xp_gain_range',
        rolled_field: 'character_class_specialty_xp_gain',
        label: 'Character Class Specialty XP Gain',
      },
      {
        range_field: 'crafting_skill_bonus_range',
        rolled_field: 'crafting_skill_bonus',
        label: 'Crafting Skill Bonus',
      },
    ],
  },
  {
    title: 'Currency and Drops',
    fields: [
      {
        range_field: 'gold_gain_range',
        rolled_field: 'gold_gain',
        label: 'Gold Gain',
      },
      {
        range_field: 'gold_dust_gain_range',
        rolled_field: 'gold_dust_gain',
        label: 'Gold Dust Gain',
      },
      {
        range_field: 'shards_gain_range',
        rolled_field: 'shards_gain',
        label: 'Shards Gain',
      },
      {
        range_field: 'copper_coin_gain_range',
        rolled_field: 'copper_coin_gain',
        label: 'Copper Coin Gain',
      },
      {
        range_field: 'item_drop_chance_increase_range',
        rolled_field: 'item_drop_chance_increase',
        label: 'Item Drop Chance Increase',
      },
      {
        range_field: 'unique_item_drop_chance_increase_range',
        rolled_field: 'unique_item_drop_chance_increase',
        label: 'Unique Item Drop Chance Increase',
      },
      {
        range_field: 'mythic_item_drop_chance_increase_range',
        rolled_field: 'mythic_item_drop_chance_increase',
        label: 'Mythic Item Drop Chance Increase',
      },
      {
        range_field: 'cosmic_item_drop_chance_increase_range',
        rolled_field: 'cosmic_item_drop_chance_increase',
        label: 'Cosmic Item Drop Chance Increase',
      },
    ],
  },
  {
    title: 'Enemy Combat',
    fields: [
      {
        range_field: 'character_power_reduction_range',
        rolled_field: 'character_power_reduction',
        label: 'Character Power Reduction',
      },
      {
        range_field: 'enemy_strength_increase_range',
        rolled_field: 'enemy_strength_increase',
        label: 'Enemy Strength Increase',
      },
      {
        range_field: 'enemy_healing_increase_range',
        rolled_field: 'enemy_healing_increase',
        label: 'Enemy Healing Increase',
      },
      {
        range_field: 'enemy_spell_evasion_range',
        rolled_field: 'enemy_spell_evasion',
        label: 'Enemy Spell Evasion',
      },
      {
        range_field: 'enemy_affix_resistance_range',
        rolled_field: 'enemy_affix_resistance',
        label: 'Enemy Affix Resistance',
      },
      {
        range_field: 'enemy_entrancing_chance_range',
        rolled_field: 'enemy_entrancing_chance',
        label: 'Enemy Entrancing Chance',
      },
      {
        range_field: 'enemy_devouring_light_chance_range',
        rolled_field: 'enemy_devouring_light_chance',
        label: 'Enemy Devouring Light Chance',
      },
      {
        range_field: 'enemy_devouring_darkness_chance_range',
        rolled_field: 'enemy_devouring_darkness_chance',
        label: 'Enemy Devouring Darkness Chance',
      },
      {
        range_field: 'enemy_ambush_chance_range',
        rolled_field: 'enemy_ambush_chance',
        label: 'Enemy Ambush Chance',
      },
      {
        range_field: 'enemy_ambush_resistance_range',
        rolled_field: 'enemy_ambush_resistance',
        label: 'Enemy Ambush Resistance',
      },
      {
        range_field: 'enemy_counter_chance_range',
        rolled_field: 'enemy_counter_chance',
        label: 'Enemy Counter Chance',
      },
      {
        range_field: 'enemy_counter_resistance_range',
        rolled_field: 'enemy_counter_resistance',
        label: 'Enemy Counter Resistance',
      },
    ],
  },
  {
    title: 'Monster Rewards',
    fields: [
      {
        range_field: 'enemy_quest_item_drop_chance_increase_range',
        rolled_field: 'enemy_quest_item_drop_chance_increase',
        label: 'Enemy Quest Item Drop Chance Increase',
      },
      {
        range_field: 'monster_xp_increase_range',
        rolled_field: 'monster_xp_increase',
        label: 'Monster XP Increase',
      },
      {
        range_field: 'monster_gold_drop_increase_range',
        rolled_field: 'monster_gold_drop_increase',
        label: 'Monster Gold Drop Increase',
      },
    ],
  },
];
