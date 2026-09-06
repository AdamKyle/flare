import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassFormDefinition from '../../api/definitions/class-form-definition';
import ClassFormOptionsDefinition from '../../api/definitions/class-form-options-definition';
import ClassFormErrorsDefinition from '../../definitions/class-form-errors-definition';
import ClassFormStateDefinition from '../../definitions/class-form-state-definition';

export default interface UseClassFormDefinition {
  form_state: ClassFormStateDefinition;
  update_field: <K extends keyof ClassFormStateDefinition>(
    field: K,
    value: ClassFormStateDefinition[K]
  ) => void;
  form_options: ClassFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: ClassFormErrorsDefinition;
  form_error: string | null;
  submit: () => Promise<ClassFormDefinition | null>;
  validate_step: (step_index: number) => boolean;
}
