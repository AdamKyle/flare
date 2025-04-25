interface CharacterKingdoms {
  name: string;
  x_position: number;
  y_position: number;
  id: number;
}

export default interface BaseMapApiDefinition {
  tiles: string[][];
  character_kingdoms: CharacterKingdoms[];
}
