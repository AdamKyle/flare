import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import PlayerGameMapDetailDefinition from '../../../types/player-game-map-detail-definition';

export default interface UsePlayerGameMapDetailDefinition {
  game_map: PlayerGameMapDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
