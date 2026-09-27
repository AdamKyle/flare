import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BuildingFormDefinition from '../../definitions/building-form-definition';
import BuildingRequestDefinition from '../../definitions/building-request-definition';

export default interface UseSaveBuildingDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    building_id: number | null,
    payload: BuildingRequestDefinition
  ) => Promise<BuildingFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
