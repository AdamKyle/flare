export default interface MonsterFormStateDefinition {
  name: string;
  damage_stat: string;
  game_map_id: number | null;
  max_level: string;
  xp: string;
  gold: string;
  health_range: string;
  attack_range: string;
  drop_check: string;
  only_for_location_type: number | null;

  str: string;
  dur: string;
  dex: string;
  chr: string;
  int: string;
  agi: string;
  focus: string;
  ac: string;

  accuracy: string;
  dodge: string;
  criticality: string;
  ambush_chance: string;
  ambush_resistance: string;
  counter_chance: string;
  counter_resistance: string;

  can_cast: boolean;
  max_spell_damage: string;
  casting_accuracy: string;
  spell_evasion: string;
  max_affix_damage: string;
  affix_resistance: string;
  healing_percentage: string;
  entrancing_chance: string;
  devouring_light_chance: string;
  devouring_darkness_chance: string;
  life_stealing_resistance: string;

  quest_item_id: number | null;
  quest_item_drop_chance: string;
  is_celestial_entity: boolean;
  celestial_type: string;
  gold_cost: string;
  gold_dust_cost: string;
  shards: string;

  is_raid_monster: boolean;
  is_raid_boss: boolean;
  raid_special_attack_type: number | null;
  fire_atonement: string;
  ice_atonement: string;
  water_atonement: string;
}
