interface CharacterKingdoms {
  name: string;
  x_position: number;
  y_position: number;
  id: number;
}

interface Locations {
  id: number;
  name: string;
  x_position: number;
  y_position: number;
  is_port: boolean;
  is_corrupted: boolean;
}

export interface TimeOutDetails {
  can_move: boolean;
  time_left: number;
  show_timer: boolean;
}

export interface CharacterPosition {
  x_position: number;
  y_position: number;
}

export default interface BaseMapApiDefinition {
  tiles: string[][];
  character_kingdoms: CharacterKingdoms[];
  locations: Locations[];
  character_position: CharacterPosition;
  time_out_details: TimeOutDetails;
}
