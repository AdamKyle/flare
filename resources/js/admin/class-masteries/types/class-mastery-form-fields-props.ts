import ClassMasteryFormOptionsDefinition from '../api/definitions/class-mastery-form-options-definition';
import ClassMasteryFormErrorsDefinition from '../definitions/class-mastery-form-errors-definition';
import ClassMasteryFormStateDefinition from '../definitions/class-mastery-form-state-definition';

export default interface ClassMasteryFormFieldsProps {
  state: ClassMasteryFormStateDefinition;
  errors: ClassMasteryFormErrorsDefinition;
  form_options: ClassMasteryFormOptionsDefinition;
  on_change: <K extends keyof ClassMasteryFormStateDefinition>(
    field: K,
    value: ClassMasteryFormStateDefinition[K]
  ) => void;
}
