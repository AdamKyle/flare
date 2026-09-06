import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MapGemDetailDefinition from '../../definitions/map-gem-detail-definition';

export default interface UseMapGemDetailDefinition {
  map_gem: MapGemDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
