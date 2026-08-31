import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MonsterFormOptionsDefinition from '../../definitions/monster-form-options-definition';

export default interface UseMonsterFormOptionsDefinition {
  form_options: MonsterFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
