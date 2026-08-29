import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { Dispatch, SetStateAction } from 'react';

import NpcQuestDefinition from '../../definitions/npc-quest-definition';

export default interface UseNpcQuestsDefinition {
  data: NpcQuestDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  page: number;
  set_page: Dispatch<SetStateAction<number>>;
  total_pages: number;
  total_records: number;
  refresh: () => void;
}
