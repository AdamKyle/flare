export default interface AreaGemSourceDefinition {
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
