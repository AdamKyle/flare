import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseAttackMonsterInitiationResponse from './use-attack-monster-initiation-response';
import UseAttackMonsterRequestParams from './use-attack-monster-request-params';
import { StateSetter } from '../../../../../../../../types/state-setter-type';

export default interface UseAttackMonsterDefinition {
  data: UseAttackMonsterInitiationResponse | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  setRequestData: StateSetter<UseAttackMonsterRequestParams>;
}
