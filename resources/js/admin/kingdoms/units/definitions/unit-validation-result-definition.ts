import UnitFormErrorsDefinition from './unit-form-errors-definition';

export default interface UnitValidationResultDefinition {
  is_valid: boolean;
  field_errors: UnitFormErrorsDefinition;
}
