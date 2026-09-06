import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationGemDetailDefinition from '../../definitions/location-gem-detail-definition';

export default interface UseLocationGemDetailDefinition {
  location_gem: LocationGemDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
