export default interface LocationGemListDefinition {
  id: number;
  name: string;
  game_map: {
    id: number;
    name: string;
  };
  location: {
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
