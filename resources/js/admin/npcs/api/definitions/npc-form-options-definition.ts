export interface NpcTypeOption {
  value: number;
  label: string;
}

export default interface NpcFormOptionsDefinition {
  game_map: {
    id: number;
    name: string;
  };
  npc_types: NpcTypeOption[];
  coordinates: {
    x: number[];
    y: number[];
  };
}
