import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import NpcDetailDefinition from '../../definitions/npc-detail-definition';

export default interface UseNpcDetailDefinition {
  npc: NpcDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
