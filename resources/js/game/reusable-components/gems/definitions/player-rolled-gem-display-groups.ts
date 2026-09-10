import { RolledGemDisplayGroup } from '../types/rolled-gem-display-group';

export const MAP_GEM_PLAYER_DISPLAY_GROUPS: RolledGemDisplayGroup[] = [
  {
    title: 'Player Rewards',
    fields: [
      { rolled_field: 'character_xp_bonus', label: 'Character XP Bonus' },
      {
        rolled_field: 'character_class_rank_xp_bonus',
        label: 'Character Class Rank XP Bonus',
      },
      {
        rolled_field: 'kingdom_passive_training_reduction',
        label: 'Kingdom Passive Training Reduction',
      },
      {
        rolled_field: 'character_class_specialty_xp_gain',
        label: 'Character Class Specialty XP Gain',
      },
      { rolled_field: 'crafting_skill_bonus', label: 'Crafting Skill Bonus' },
    ],
  },
  {
    title: 'Currency and Drops',
    fields: [
      { rolled_field: 'gold_gain', label: 'Gold Gain' },
      { rolled_field: 'gold_dust_gain', label: 'Gold Dust Gain' },
      { rolled_field: 'shards_gain', label: 'Shards Gain' },
      { rolled_field: 'copper_coin_gain', label: 'Copper Coin Gain' },
      {
        rolled_field: 'item_drop_chance_increase',
        label: 'Item Drop Chance Increase',
      },
      {
        rolled_field: 'unique_item_drop_chance_increase',
        label: 'Unique Item Drop Chance Increase',
      },
      {
        rolled_field: 'mythic_item_drop_chance_increase',
        label: 'Mythic Item Drop Chance Increase',
      },
      {
        rolled_field: 'cosmic_item_drop_chance_increase',
        label: 'Cosmic Item Drop Chance Increase',
      },
    ],
  },
  {
    title: 'Enemy Combat',
    fields: [
      {
        rolled_field: 'character_power_reduction',
        label: 'Character Power Reduction',
      },
      {
        rolled_field: 'enemy_strength_increase',
        label: 'Enemy Strength Increase',
      },
      {
        rolled_field: 'enemy_healing_increase',
        label: 'Enemy Healing Increase',
      },
      { rolled_field: 'enemy_spell_evasion', label: 'Enemy Spell Evasion' },
      {
        rolled_field: 'enemy_affix_resistance',
        label: 'Enemy Affix Resistance',
      },
      {
        rolled_field: 'enemy_entrancing_chance',
        label: 'Enemy Entrancing Chance',
      },
      {
        rolled_field: 'enemy_devouring_light_chance',
        label: 'Enemy Devouring Light Chance',
      },
      {
        rolled_field: 'enemy_devouring_darkness_chance',
        label: 'Enemy Devouring Darkness Chance',
      },
      { rolled_field: 'enemy_ambush_chance', label: 'Enemy Ambush Chance' },
      {
        rolled_field: 'enemy_ambush_resistance',
        label: 'Enemy Ambush Resistance',
      },
      { rolled_field: 'enemy_counter_chance', label: 'Enemy Counter Chance' },
      {
        rolled_field: 'enemy_counter_resistance',
        label: 'Enemy Counter Resistance',
      },
    ],
  },
  {
    title: 'Monster Rewards',
    fields: [
      {
        rolled_field: 'enemy_quest_item_drop_chance_increase',
        label: 'Enemy Quest Item Drop Chance Increase',
      },
      { rolled_field: 'monster_xp_increase', label: 'Monster XP Increase' },
      {
        rolled_field: 'monster_gold_drop_increase',
        label: 'Monster Gold Drop Increase',
      },
    ],
  },
];

export const LOCATION_GEM_PLAYER_DISPLAY_GROUPS: RolledGemDisplayGroup[] = [
  {
    title: 'Player Rewards',
    fields: [
      { rolled_field: 'character_xp_bonus', label: 'Character XP Bonus' },
      {
        rolled_field: 'character_class_rank_xp_bonus',
        label: 'Character Class Rank XP Bonus',
      },
      {
        rolled_field: 'kingdom_passive_training_reduction',
        label: 'Kingdom Passive Training Reduction',
      },
      {
        rolled_field: 'character_class_specialty_xp_gain',
        label: 'Character Class Specialty XP Gain',
      },
      { rolled_field: 'crafting_skill_bonus', label: 'Crafting Skill Bonus' },
    ],
  },
  {
    title: 'Currency and Drops',
    fields: [
      { rolled_field: 'gold_gain', label: 'Gold Gain' },
      { rolled_field: 'gold_dust_gain', label: 'Gold Dust Gain' },
      { rolled_field: 'shards_gain', label: 'Shards Gain' },
      { rolled_field: 'copper_coin_gain', label: 'Copper Coin Gain' },
      {
        rolled_field: 'item_drop_chance_increase',
        label: 'Item Drop Chance Increase',
      },
      {
        rolled_field: 'unique_item_drop_chance_increase',
        label: 'Unique Item Drop Chance Increase',
      },
      {
        rolled_field: 'mythic_item_drop_chance_increase',
        label: 'Mythic Item Drop Chance Increase',
      },
      {
        rolled_field: 'cosmic_item_drop_chance_increase',
        label: 'Cosmic Item Drop Chance Increase',
      },
    ],
  },
  {
    title: 'Enemy Combat',
    fields: [
      {
        rolled_field: 'enemy_strength_increase',
        label: 'Enemy Strength Increase',
      },
      {
        rolled_field: 'enemy_healing_increase',
        label: 'Enemy Healing Increase',
      },
      { rolled_field: 'enemy_spell_evasion', label: 'Enemy Spell Evasion' },
      {
        rolled_field: 'enemy_affix_resistance',
        label: 'Enemy Affix Resistance',
      },
      {
        rolled_field: 'enemy_entrancing_chance',
        label: 'Enemy Entrancing Chance',
      },
      {
        rolled_field: 'enemy_devouring_light_chance',
        label: 'Enemy Devouring Light Chance',
      },
      {
        rolled_field: 'enemy_devouring_darkness_chance',
        label: 'Enemy Devouring Darkness Chance',
      },
      { rolled_field: 'enemy_ambush_chance', label: 'Enemy Ambush Chance' },
      {
        rolled_field: 'enemy_ambush_resistance',
        label: 'Enemy Ambush Resistance',
      },
      { rolled_field: 'enemy_counter_chance', label: 'Enemy Counter Chance' },
      {
        rolled_field: 'enemy_counter_resistance',
        label: 'Enemy Counter Resistance',
      },
    ],
  },
  {
    title: 'Monster Rewards',
    fields: [
      {
        rolled_field: 'enemy_quest_item_drop_chance_increase',
        label: 'Enemy Quest Item Drop Chance Increase',
      },
      { rolled_field: 'monster_xp_increase', label: 'Monster XP Increase' },
      {
        rolled_field: 'monster_gold_drop_increase',
        label: 'Monster Gold Drop Increase',
      },
    ],
  },
];
