import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import PassiveSkillFormDefinition from '../../definitions/passive-skill-form-definition';

export default interface UsePassiveSkillForEditDefinition {
  passive_skill: PassiveSkillFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
