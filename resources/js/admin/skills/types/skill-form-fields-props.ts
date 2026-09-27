import SkillFormOptionsDefinition from '../api/definitions/skill-form-options-definition';
import SkillFormErrorsDefinition from '../definitions/skill-form-errors-definition';
import SkillFormStateDefinition from '../definitions/skill-form-state-definition';

export default interface SkillFormFieldsProps {
  state: SkillFormStateDefinition;
  errors: SkillFormErrorsDefinition;
  form_options: SkillFormOptionsDefinition;
  on_change: <K extends keyof SkillFormStateDefinition>(
    field: K,
    value: SkillFormStateDefinition[K]
  ) => void;
}
