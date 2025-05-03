interface CharacterKingdoms {
  name: string;
  x_position: number;
  y_position: number;
  id: number;
}

export interface CharacterPosition {
  x_position: number;
  y_position: number;
}

export default interface BaseMapApiDefinition {
  tiles: string[][];
  character_kingdoms: CharacterKingdoms[];
  character_position: CharacterPosition;
}
