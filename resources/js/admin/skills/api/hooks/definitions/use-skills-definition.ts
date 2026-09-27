import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SkillListDefinition from '../../definitions/skill-list-definition';
import { SkillListResponseDefinition } from '../../definitions/skill-list-response-definition';

export default interface UseSkillsDefinition {
  data: SkillListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: SkillListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
