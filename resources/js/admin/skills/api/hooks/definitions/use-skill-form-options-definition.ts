import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SkillFormOptionsDefinition from '../../definitions/skill-form-options-definition';

export default interface UseSkillFormOptionsDefinition {
  form_options: SkillFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
