import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapRelatedLocationDefinition from '../../definitions/game-map-related-location-definition';

export default interface UseGameMapRelatedLocationsDefinition {
  data: GameMapRelatedLocationDefinition[];
  loading: boolean;
  is_loading_more: boolean;
  error: AxiosErrorDefinition | null;
  on_end_reached: () => void;
  refresh: () => void;
}
