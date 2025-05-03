interface CharacterKingdoms {
  name: string;
  x_position: number;
  y_position: number;
  id: number;
}

interface CharacterPosition {
  x_position: number;
  y_position: number;
  position_x: number;
  position_y: number;
}

export default interface BaseMapApiDefinition {
  tiles: string[][];
  character_kingdoms: CharacterKingdoms[];
  character_position: CharacterPosition;
}
