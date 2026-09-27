import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BuildingDetailDefinition from '../../definitions/building-detail-definition';

export default interface UseBuildingDetailDefinition {
  building: BuildingDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
