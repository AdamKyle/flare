import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapRelatedMonsterDefinition from '../../definitions/game-map-related-monster-definition';

export default interface UseGameMapRelatedMonstersDefinition {
  data: GameMapRelatedMonsterDefinition[];
  loading: boolean;
  is_loading_more: boolean;
  error: AxiosErrorDefinition | null;
  on_end_reached: () => void;
  refresh: () => void;
}
