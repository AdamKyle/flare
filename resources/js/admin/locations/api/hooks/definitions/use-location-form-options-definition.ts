import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationFormOptionsDefinition from '../../definitions/location-form-options-definition';

export default interface UseLocationFormOptionsDefinition {
  form_options: LocationFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
