import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MapGemListDefinition from '../../definitions/map-gem-list-definition';
import { MapGemListResponseDefinition } from '../../definitions/map-gem-list-response-definition';

export default interface UseMapGemsDefinition {
  data: MapGemListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: MapGemListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  game_map_id: number | null;
  set_game_map_id: (game_map_id: number | null) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
