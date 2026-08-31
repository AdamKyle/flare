import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapRelatedQuestDefinition from '../../definitions/game-map-related-quest-definition';

export default interface UseGameMapRelatedQuestsDefinition {
  data: GameMapRelatedQuestDefinition[];
  loading: boolean;
  is_loading_more: boolean;
  error: AxiosErrorDefinition | null;
  on_end_reached: () => void;
  refresh: () => void;
}
