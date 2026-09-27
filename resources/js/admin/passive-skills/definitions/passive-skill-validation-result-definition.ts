import PassiveSkillFormErrorsDefinition from './passive-skill-form-errors-definition';

export default interface PassiveSkillValidationResultDefinition {
  is_valid: boolean;
  field_errors: PassiveSkillFormErrorsDefinition;
  form_error: string | null;
}
