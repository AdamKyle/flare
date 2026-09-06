import RaceFormErrorsDefinition from './race-form-errors-definition';

export default interface RaceValidationResultDefinition {
  is_valid: boolean;
  field_errors: RaceFormErrorsDefinition;
  form_error: string | null;
}
