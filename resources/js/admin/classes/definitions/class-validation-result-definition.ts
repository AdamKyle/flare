import ClassFormErrorsDefinition from './class-form-errors-definition';

export default interface ClassValidationResultDefinition {
  is_valid: boolean;
  field_errors: ClassFormErrorsDefinition;
  form_error: string | null;
}
