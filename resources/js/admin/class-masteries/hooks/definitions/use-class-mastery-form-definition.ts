import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassMasteryFormDefinition from '../../api/definitions/class-mastery-form-definition';
import ClassMasteryFormOptionsDefinition from '../../api/definitions/class-mastery-form-options-definition';
import ClassMasteryFormErrorsDefinition from '../../definitions/class-mastery-form-errors-definition';
import ClassMasteryFormStateDefinition from '../../definitions/class-mastery-form-state-definition';

export default interface UseClassMasteryFormDefinition {
  form_state: ClassMasteryFormStateDefinition;
  update_field: <K extends keyof ClassMasteryFormStateDefinition>(
    field: K,
    value: ClassMasteryFormStateDefinition[K]
  ) => void;
  form_options: ClassMasteryFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: ClassMasteryFormErrorsDefinition;
  form_error: string | null;
  submit: () => Promise<ClassMasteryFormDefinition | null>;
  validate_step: (step_index: number) => boolean;
}
