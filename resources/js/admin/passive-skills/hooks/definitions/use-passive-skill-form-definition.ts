import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import PassiveSkillFormDefinition from '../../api/definitions/passive-skill-form-definition';
import PassiveSkillFormOptionsDefinition from '../../api/definitions/passive-skill-form-options-definition';
import PassiveSkillFormErrorsDefinition from '../../definitions/passive-skill-form-errors-definition';
import PassiveSkillFormStateDefinition from '../../definitions/passive-skill-form-state-definition';

export default interface UsePassiveSkillFormDefinition {
  form_state: PassiveSkillFormStateDefinition;
  update_field: <K extends keyof PassiveSkillFormStateDefinition>(
    field: K,
    value: PassiveSkillFormStateDefinition[K]
  ) => void;
  form_options: PassiveSkillFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: PassiveSkillFormErrorsDefinition;
  submit: () => Promise<PassiveSkillFormDefinition | null>;
  validate_step: (step_index: number) => boolean;
}
