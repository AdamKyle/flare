import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { Dispatch, SetStateAction } from 'react';

import { LocationType } from '../../../enums/location-type';
import LocationListDefinition from '../../definitions/location-list-definition';
import { LocationListResponseDefinition } from '../../definitions/location-list-response-definition';

export default interface UseLocationsDefinition {
  data: LocationListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: LocationListResponseDefinition | null;
  search_text: string;
  set_search_text: Dispatch<SetStateAction<string>>;
  page: number;
  set_page: Dispatch<SetStateAction<number>>;
  game_map_id: number | null;
  set_game_map_id: (game_map_id: number | null) => void;
  type: LocationType | null;
  set_type: (type: LocationType | null) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
