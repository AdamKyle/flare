import CharacterKingdomsPositionDefinitions from '../../../../../api-definitions/map-details/character-kingdoms-position-definitions';
import LocationsPositionDefinition from '../../../../../api-definitions/map-details/locations-position-definition';

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
  character_kingdoms: CharacterKingdomsPositionDefinitions[];
  locations: LocationsPositionDefinition[];
  npc_kingdoms: CharacterKingdomsPositionDefinitions[];
  enemy_kingdoms: CharacterKingdomsPositionDefinitions[];
  character_position: CharacterPosition;
  time_out_details: TimeOutDetails;
}
