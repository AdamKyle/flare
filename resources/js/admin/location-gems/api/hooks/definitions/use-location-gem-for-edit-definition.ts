import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationGemFormDefinition from '../../definitions/location-gem-form-definition';

export default interface UseLocationGemForEditDefinition {
  location_gem: LocationGemFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
