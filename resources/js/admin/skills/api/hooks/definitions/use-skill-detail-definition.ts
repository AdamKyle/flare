import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SkillDetailDefinition from '../../definitions/skill-detail-definition';

export default interface UseSkillDetailDefinition {
  skill: SkillDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
