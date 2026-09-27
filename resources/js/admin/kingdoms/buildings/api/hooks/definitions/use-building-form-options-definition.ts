import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BuildingFormOptionsDefinition from '../../definitions/building-form-options-definition';

export default interface UseBuildingFormOptionsDefinition {
  form_options: BuildingFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
