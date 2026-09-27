import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BuildingFormDefinition from '../../definitions/building-form-definition';

export default interface UseBuildingForEditDefinition {
  building: BuildingFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
