import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MapGemFormOptionsDefinition from '../../definitions/map-gem-form-options-definition';

export default interface UseMapGemFormOptionsDefinition {
  form_options: MapGemFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
