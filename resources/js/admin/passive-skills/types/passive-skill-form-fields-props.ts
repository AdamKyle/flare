import PassiveSkillFormOptionsDefinition from '../api/definitions/passive-skill-form-options-definition';
import PassiveSkillFormErrorsDefinition from '../definitions/passive-skill-form-errors-definition';
import PassiveSkillFormStateDefinition from '../definitions/passive-skill-form-state-definition';

export default interface PassiveSkillFormFieldsProps {
  state: PassiveSkillFormStateDefinition;
  errors: PassiveSkillFormErrorsDefinition;
  form_options: PassiveSkillFormOptionsDefinition;
  passive_skill_id: number | null;
  on_change: <K extends keyof PassiveSkillFormStateDefinition>(
    field: K,
    value: PassiveSkillFormStateDefinition[K]
  ) => void;
}
