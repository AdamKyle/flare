import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MonsterDetailDefinition from '../../../../../game/reusable-components/monster/api/definitions/monster-detail-definition';

export default interface UseMonsterDetailDefinition {
  monster: MonsterDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
