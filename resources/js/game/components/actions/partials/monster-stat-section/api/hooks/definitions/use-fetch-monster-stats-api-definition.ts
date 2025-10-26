import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SetMonsterPartsDefinition from './set-monster-params-definition';
import MonsterDefinition from '../../../../../../../api-definitions/monsters/monster-definition';

export default interface UseFetchMonsterStatsApiDefinition {
  data: MonsterDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
  setRequestParams: (requestParams: SetMonsterPartsDefinition) => void;
}
