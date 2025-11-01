import { CharacterPosition } from '../../../../../../map-section/api/hooks/definitions/base-map-api-definition';

export default interface UseTeleportPlayerApiResponseDefinition {
  character_position_data: CharacterPosition;
  has_traversed: boolean;
}
