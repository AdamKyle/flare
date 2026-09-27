import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UnitFormDefinition from '../../definitions/unit-form-definition';
import UnitRequestDefinition from '../../definitions/unit-request-definition';

export default interface UseSaveUnitDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    unit_id: number | null,
    payload: UnitRequestDefinition
  ) => Promise<UnitFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
