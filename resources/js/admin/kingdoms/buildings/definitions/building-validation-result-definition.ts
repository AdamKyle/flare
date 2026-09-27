import BuildingFormErrorsDefinition from './building-form-errors-definition';

export default interface BuildingValidationResultDefinition {
  is_valid: boolean;
  field_errors: BuildingFormErrorsDefinition;
}
