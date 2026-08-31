export interface MonsterFormOptionItem {
  value: number;
  label: string;
}

export default interface MonsterFormOptionsDefinition {
  game_maps: MonsterFormOptionItem[];
  quest_items: MonsterFormOptionItem[];
  damage_stats: string[];
  location_types: number[];
  raid_special_attack_types: number[];
}
