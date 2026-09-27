import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import PassiveSkillFormOptionsDefinition from '../../definitions/passive-skill-form-options-definition';

export default interface UsePassiveSkillFormOptionsDefinition {
  form_options: PassiveSkillFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
