import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MapGemFormDefinition from '../../definitions/map-gem-form-definition';

export default interface UseMapGemForEditDefinition {
  map_gem: MapGemFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
