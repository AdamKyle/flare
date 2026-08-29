import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationDetailDefinition from '../../definitions/location-detail-definition';

export default interface UseLocationDetailDefinition {
  location: LocationDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
