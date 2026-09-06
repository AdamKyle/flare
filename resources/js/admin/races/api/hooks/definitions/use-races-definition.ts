import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import RaceDefinition from '../../definitions/race-definition';
import { RaceListResponseDefinition } from '../../definitions/race-list-response-definition';

export default interface UseRacesDefinition {
  data: RaceDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: RaceListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
