export default interface MonsterDefinition {
  id: number;
  name: string;
  map_name: string;
  damage_stat: 'str' | 'dex' | 'int' | 'agi' | 'dur' | 'chr' | 'focus';

  str: number;
  dur: number;
  dex: number;
  chr: number;
  int: number;
  agi: number;
  focus: number;

  ac: number;

  health_range: string;
  attack_range: string;

  accuracy: number;
  dodge: number;
  casting_accuracy: number;
  criticality: number;

  max_level: number;

  spell_damage: number;
  spell_evasion: number;
  affix_resistance: number;
  max_affix_damage: number;
  max_healing: number;

  entrancing_chance: number;
  devouring_light_chance: number;
  devouring_darkness_chance: number;
  ambush_chance: number;
  ambush_resistance_chance: number;
  counter_chance: number;
  counter_resistance_chance: number;

  increases_damage_by: number;

  is_special: boolean;
  is_raid_monster: boolean;
  is_raid_boss: boolean;

  fire_atonement: number | null;
  ice_atonement: number | null;
  water_atonement: number | null;

  life_stealing_resistance: number;

  raid_special_attack_type: number | null;
  only_for_location_type: number | null;

  drop_chance: number;
  xp: number;
  gold: number;
  gold_cost: number | null;
  gold_dust_cost: number | null;
  shard_reward: number;

  is_celestial_entity: boolean;
}
