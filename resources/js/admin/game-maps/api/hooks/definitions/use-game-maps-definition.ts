import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { Dispatch, SetStateAction } from 'react';

import { GameMapPlane } from '../../../enums/game-map-plane';
import { GameMapType } from '../../../enums/game-map-type';
import GameMapDefinition from '../../definitions/game-map-definition';
import { GameMapListResponseDefinition } from '../../definitions/game-map-list-response-definition';

export default interface UseGameMapsDefinition {
  data: GameMapDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: GameMapListResponseDefinition | null;
  search_text: string;
  set_search_text: Dispatch<SetStateAction<string>>;
  page: number;
  set_page: Dispatch<SetStateAction<number>>;
  plane: GameMapPlane | null;
  set_plane: (plane: GameMapPlane | null) => void;
  map_type: GameMapType | null;
  set_map_type: (map_type: GameMapType | null) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
