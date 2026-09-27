import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SkillFormDefinition from '../../api/definitions/skill-form-definition';
import SkillFormOptionsDefinition from '../../api/definitions/skill-form-options-definition';
import SkillFormErrorsDefinition from '../../definitions/skill-form-errors-definition';
import SkillFormStateDefinition from '../../definitions/skill-form-state-definition';

export default interface UseSkillFormDefinition {
  form_state: SkillFormStateDefinition;
  update_field: <K extends keyof SkillFormStateDefinition>(
    field: K,
    value: SkillFormStateDefinition[K]
  ) => void;
  form_options: SkillFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: SkillFormErrorsDefinition;
  submit: () => Promise<SkillFormDefinition | null>;
  validate_step: (step_index: number) => boolean;
}
