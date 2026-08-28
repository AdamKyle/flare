import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import NpcDefinition from '../../definitions/npc-definition';

export default interface UseNpcDefinition {
  npc: NpcDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
