import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassListDefinition from '../../definitions/class-list-definition';
import { ClassListResponseDefinition } from '../../definitions/class-list-response-definition';

export default interface UseClassesDefinition {
  data: ClassListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: ClassListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
