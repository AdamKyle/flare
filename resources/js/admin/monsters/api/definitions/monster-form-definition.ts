export default interface MonsterFormDefinition {
  id: number;
  name: string;
  damage_stat: string;
  game_map_id: number | null;
  max_level: number;
  xp: number;
  gold: number;
  health_range: string;
  attack_range: string;
  drop_check: number;
  only_for_location_type: number | null;

  str: number;
  dur: number;
  dex: number;
  chr: number;
  int: number;
  agi: number;
  focus: number;
  ac: number;

  accuracy: number | null;
  dodge: number | null;
  criticality: number | null;
  ambush_chance: number | null;
  ambush_resistance: number | null;
  counter_chance: number | null;
  counter_resistance: number | null;

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

  quest_item_id: number | null;
  quest_item_drop_chance: number | null;
  is_celestial_entity: boolean;
  celestial_type: number | null;
  gold_cost: number | null;
  gold_dust_cost: number | null;
  shards: number | null;

  is_raid_monster: boolean;
  is_raid_boss: boolean;
  raid_special_attack_type: number | null;
  fire_atonement: number | null;
  ice_atonement: number | null;
  water_atonement: number | null;
}
