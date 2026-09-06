import QuestItemFactualDefinition, {
  GameMapIdentityDefinition,
} from '../../../quest-item/types/quest-item-factual-definition';

export interface MonsterIdentitySectionDefinition {
  name: string;
  damage_stat: string;
  game_map: GameMapIdentityDefinition | null;
  max_level: number;
  xp: number;
  gold: number;
  health_range: string;
  attack_range: string;
  drop_check: number;
  only_for_location_type: number | null;
}

export interface MonsterCombatSectionDefinition {
  str: number;
  dur: number;
  dex: number;
  chr: number;
  int: number;
  agi: number;
  focus: number;
  ac: number;
}

export interface MonsterProbabilitiesDefinition {
  accuracy: number | null;
  dodge: number | null;
  criticality: number | null;
  ambush_chance: number | null;
  ambush_resistance: number | null;
  counter_chance: number | null;
  counter_resistance: number | null;
}

export interface MonsterSpellSectionDefinition {
  can_cast: boolean;
  max_spell_damage: number | null;
  casting_accuracy: number | null;
  spell_evasion: number | null;
  max_affix_damage: number | null;
  affix_resistance: number | null;
  healing_percentage: number | null;
  entrancing_chance: number | null;
  devouring_light_chance: number | null;
  devouring_darkness_chance: number | null;
  life_stealing_resistance: number | null;
}

export interface MonsterQuestCelestialSectionDefinition {
  quest_item: (QuestItemFactualDefinition & { item_id: number }) | null;
  quest_item_drop_chance: number | null;
  is_celestial_entity: boolean;
  celestial_type: number | null;
  gold_cost: number | null;
  gold_dust_cost: number | null;
  shards: number | null;
}

export interface MonsterRaidSectionDefinition {
  is_raid_monster: boolean;
  is_raid_boss: boolean;
  raid_special_attack_type: number | null;
  fire_atonement: number | null;
  ice_atonement: number | null;
  water_atonement: number | null;
}

export type MonsterGemEffectContextType =
  'map' | 'location' | 'map_gem_world' | 'location_gem_world';

export interface MonsterGemEffectSourceDefinition {
  type: 'map_gem' | 'location_gem';
  profile_id: number;
  profile_name: string;
  rolled_gem_id: number;
  rolled_gem_name: string;
  monster_multiplier: number;
  reward_multiplier: number;
  reduction_multiplier: number | null;
  game_map_id: number | null;
  game_map_name: string | null;
  location_id: number | null;
  location_name: string | null;
}

export interface MonsterGemEffectChangedValueDefinition {
  field: string;
  label: string;
  base_value: number | string | null;
  effective_value: number | string | null;
  display_type: 'number' | 'range' | 'percent';
}

export interface MonsterGemEffectContextDefinition {
  key: string;
  type: MonsterGemEffectContextType;
  label: string;
  game_map: { id: number; name: string } | null;
  location: { id: number; name: string } | null;
  sources: MonsterGemEffectSourceDefinition[];
  character_power_reduction: number;
  changed_values: MonsterGemEffectChangedValueDefinition[];
}

export default interface MonsterDetailDefinition {
  id: number;
  identity: MonsterIdentitySectionDefinition;
  combat: MonsterCombatSectionDefinition;
  probabilities: MonsterProbabilitiesDefinition;
  spells_and_affixes: MonsterSpellSectionDefinition;
  quest_and_celestial: MonsterQuestCelestialSectionDefinition;
  raid_and_special: MonsterRaidSectionDefinition;
  gem_effect_contexts: MonsterGemEffectContextDefinition[];
}
