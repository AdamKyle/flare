import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UnitListDefinition from '../../definitions/unit-list-definition';
import { UnitListResponseDefinition } from '../../definitions/unit-list-response-definition';

export default interface UseUnitsDefinition {
  data: UnitListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: UnitListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
