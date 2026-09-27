import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import PassiveSkillTreeDefinition from '../../definitions/passive-skill-tree-definition';

export default interface UsePassiveSkillTreeDefinition {
  passive_skills: PassiveSkillTreeDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
