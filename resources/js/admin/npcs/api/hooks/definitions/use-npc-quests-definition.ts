import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import NpcQuestDefinition from '../../definitions/npc-quest-definition';

export default interface UseNpcQuestsDefinition {
  data: NpcQuestDefinition[];
  loading: boolean;
  is_loading_more: boolean;
  error: AxiosErrorDefinition | null;
  on_end_reached: () => void;
  refresh: () => void;
}
