import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GuideQuestListDefinition from '../../definitions/guide-quest-list-definition';
import { GuideQuestListResponseDefinition } from '../../definitions/guide-quest-list-response-definition';

export default interface UseGuideQuestsDefinition {
  data: GuideQuestListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: GuideQuestListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sortKey: string) => void;
}
