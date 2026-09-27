import SkillFormErrorsDefinition from './skill-form-errors-definition';

export default interface SkillValidationResultDefinition {
  is_valid: boolean;
  field_errors: SkillFormErrorsDefinition;
  form_error: string | null;
}
