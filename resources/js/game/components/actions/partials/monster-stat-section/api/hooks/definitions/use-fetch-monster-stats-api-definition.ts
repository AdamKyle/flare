import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SetMonsterPartsDefinition from './set-monster-params-definition';
import MonsterDetailDefinition from '../../../../../../../reusable-components/monster/api/definitions/monster-detail-definition';

export default interface UseFetchMonsterStatsApiDefinition {
  data: MonsterDetailDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
  setRequestParams: (requestParams: SetMonsterPartsDefinition) => void;
}
