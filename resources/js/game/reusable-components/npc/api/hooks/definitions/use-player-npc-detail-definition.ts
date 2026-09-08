import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import NpcFactualDefinition from '../../../types/npc-factual-definition';

export default interface UsePlayerNpcDetailDefinition {
  npc: NpcFactualDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
