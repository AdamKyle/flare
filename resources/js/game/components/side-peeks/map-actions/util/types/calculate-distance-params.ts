import { CharacterPosition } from '../../../../map-section/api/hooks/definitions/base-map-api-definition';

export default interface CalculateDistanceParams {
  character_position: CharacterPosition;
  new_character_position: CharacterPosition;
}
