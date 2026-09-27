import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BuildingListDefinition from '../../definitions/building-list-definition';
import { BuildingListResponseDefinition } from '../../definitions/building-list-response-definition';

export default interface UseBuildingsDefinition {
  data: BuildingListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: BuildingListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
