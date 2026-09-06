import ClassFormOptionsDefinition from '../api/definitions/class-form-options-definition';
import ClassFormErrorsDefinition from '../definitions/class-form-errors-definition';
import ClassFormStateDefinition from '../definitions/class-form-state-definition';

export default interface ClassFormFieldsProps {
  state: ClassFormStateDefinition;
  errors: ClassFormErrorsDefinition;
  form_options: ClassFormOptionsDefinition;
  on_change: <K extends keyof ClassFormStateDefinition>(
    field: K,
    value: ClassFormStateDefinition[K]
  ) => void;
}
