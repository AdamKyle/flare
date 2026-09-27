import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import PassiveSkillDetailDefinition from '../../definitions/passive-skill-detail-definition';

export default interface UsePassiveSkillDetailDefinition {
  passive_skill: PassiveSkillDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
