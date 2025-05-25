import CharacterKingdomsPositionDefinitions from '../../../../../../../api-definitions/map-details/character-kingdoms-position-definitions';
import LocationsPositionDefinition from '../../../../../../../api-definitions/map-details/locations-position-definition';

export interface Coordinates {
  x: number[];
  y: number[];
}

export default interface TeleportCoordinatesApiDefinition {
  character_kingdoms: CharacterKingdomsPositionDefinitions[];
  enemy_kingdoms: CharacterKingdomsPositionDefinitions[];
  npc_kingdoms: CharacterKingdomsPositionDefinitions[];
  locations: LocationsPositionDefinition[];
  coordinates: Coordinates;
}
