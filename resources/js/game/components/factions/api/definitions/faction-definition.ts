export default interface FactionDefinition {
  id: number;
  character_id: number;
  game_map_id: number;
  current_level: number;
  current_points: number;
  points_needed: number;
  maxed: boolean;
  title: string | null;
  map_name: string;
}
