import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { GameMapDetailDefinition } from '../../definitions/game-map-editor-definition';

export default interface UseGameMapDefinition {
  game_map: GameMapDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
