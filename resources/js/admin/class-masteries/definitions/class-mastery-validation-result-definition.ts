import ClassMasteryFormErrorsDefinition from './class-mastery-form-errors-definition';

export default interface ClassMasteryValidationResultDefinition {
  is_valid: boolean;
  field_errors: ClassMasteryFormErrorsDefinition;
  form_error: string | null;
}
