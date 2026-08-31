import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MonsterFormDefinition from '../../definitions/monster-form-definition';

export default interface UseMonsterForEditDefinition {
  monster: MonsterFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
