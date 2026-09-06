import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationGemFormOptionsDefinition from '../../definitions/location-gem-form-options-definition';

export default interface UseLocationGemFormOptionsDefinition {
  form_options: LocationGemFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
