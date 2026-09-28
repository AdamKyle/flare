import GemAbilityFormErrorsDefinition from './gem-ability-form-errors-definition';

export default interface GemAbilityValidationResultDefinition {
  is_valid: boolean;
  field_errors: GemAbilityFormErrorsDefinition;
  form_error: string | null;
}
