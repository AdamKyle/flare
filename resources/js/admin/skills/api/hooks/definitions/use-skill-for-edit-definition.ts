import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SkillFormDefinition from '../../definitions/skill-form-definition';

export default interface UseSkillForEditDefinition {
  skill: SkillFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
