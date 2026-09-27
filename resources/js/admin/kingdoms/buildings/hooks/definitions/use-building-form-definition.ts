import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BuildingFormDefinition from '../../api/definitions/building-form-definition';
import BuildingFormOptionsDefinition from '../../api/definitions/building-form-options-definition';
import BuildingFormErrorsDefinition from '../../definitions/building-form-errors-definition';
import BuildingFormStateDefinition from '../../definitions/building-form-state-definition';

export default interface UseBuildingFormDefinition {
  form_state: BuildingFormStateDefinition;
  update_field: <K extends keyof BuildingFormStateDefinition>(
    field: K,
    value: BuildingFormStateDefinition[K]
  ) => void;
  form_options: BuildingFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: BuildingFormErrorsDefinition;
  submit: () => Promise<BuildingFormDefinition | null>;
  validate_step: (step_index: number) => boolean;
}
