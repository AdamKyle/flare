import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapFactualDefinition from '../../../types/game-map-factual-definition';

export default interface UsePlayerGameMapDetailDefinition {
  game_map: GameMapFactualDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
