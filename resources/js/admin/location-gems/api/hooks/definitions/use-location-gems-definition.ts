import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationGemListDefinition from '../../definitions/location-gem-list-definition';
import { LocationGemListResponseDefinition } from '../../definitions/location-gem-list-response-definition';

export default interface UseLocationGemsDefinition {
  data: LocationGemListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: LocationGemListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  game_map_id: number | null;
  set_game_map_id: (game_map_id: number | null) => void;
  location_id: number | null;
  set_location_id: (location_id: number | null) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
