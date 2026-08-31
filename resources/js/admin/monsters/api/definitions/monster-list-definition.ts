export interface MonsterListGameMapDefinition {
  id: number;
  name: string;
}

export default interface MonsterListDefinition {
  id: number;
  name: string;
  game_map: MonsterListGameMapDefinition | null;
  max_level: number;
  xp: number;
  gold: number;
  is_celestial_entity: boolean;
  is_raid_monster: boolean;
  is_raid_boss: boolean;
}
