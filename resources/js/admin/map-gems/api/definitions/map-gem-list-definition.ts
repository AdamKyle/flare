export default interface MapGemListDefinition {
  id: number;
  name: string;
  game_map: {
    id: number;
    name: string;
  };
  roll_count: number;
  rolled_gem: {
    id: number;
    name: string;
    roll_number: number;
  } | null;
}
