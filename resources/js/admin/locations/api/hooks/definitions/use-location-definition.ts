import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationDefinition from '../../definitions/location-definition';

export default interface UseLocationDefinition {
  location: LocationDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
