import LocationGemFormErrorsDefinition from './location-gem-form-errors-definition';

export default interface LocationGemValidationResultDefinition {
  is_valid: boolean;
  field_errors: LocationGemFormErrorsDefinition;
  form_error: string | null;
}
