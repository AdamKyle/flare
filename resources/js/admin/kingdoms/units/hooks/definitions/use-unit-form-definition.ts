import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UnitFormDefinition from '../../api/definitions/unit-form-definition';
import UnitFormErrorsDefinition from '../../definitions/unit-form-errors-definition';
import UnitFormStateDefinition from '../../definitions/unit-form-state-definition';

export default interface UseUnitFormDefinition {
  form_state: UnitFormStateDefinition;
  update_field: <K extends keyof UnitFormStateDefinition>(
    field: K,
    value: UnitFormStateDefinition[K]
  ) => void;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: UnitFormErrorsDefinition;
  submit: () => Promise<UnitFormDefinition | null>;
  validate_step: (step_index: number) => boolean;
}
