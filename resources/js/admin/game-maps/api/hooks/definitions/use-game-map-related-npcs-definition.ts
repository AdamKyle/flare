import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapRelatedNpcDefinition from '../../definitions/game-map-related-npc-definition';

export default interface UseGameMapRelatedNpcsDefinition {
  data: GameMapRelatedNpcDefinition[];
  loading: boolean;
  is_loading_more: boolean;
  error: AxiosErrorDefinition | null;
  on_end_reached: () => void;
  refresh: () => void;
}
