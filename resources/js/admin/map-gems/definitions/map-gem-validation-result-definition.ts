import MapGemFormErrorsDefinition from './map-gem-form-errors-definition';

export default interface MapGemValidationResultDefinition {
  is_valid: boolean;
  field_errors: MapGemFormErrorsDefinition;
  form_error: string | null;
}
