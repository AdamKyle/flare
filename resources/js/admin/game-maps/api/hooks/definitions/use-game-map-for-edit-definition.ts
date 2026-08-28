import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapFormResponseDefinition from '../../../definitions/game-map-form-response-definition';

export default interface UseGameMapForEditDefinition {
  game_map: GameMapFormResponseDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
