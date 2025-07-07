import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationDetailsApi from '../../api-definitions/location-details-api';

export default interface UseFetchLocationDetailsApiDefinition {
  data: LocationDetailsApi | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
