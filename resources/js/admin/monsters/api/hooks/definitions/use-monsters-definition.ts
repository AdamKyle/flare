import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { StateSetter } from '../../../../../types/state-setter-type';
import { MonsterListCategory } from '../../../enums/monster-list-category';
import MonsterListDefinition from '../../definitions/monster-list-definition';
import { MonsterListResponseDefinition } from '../../definitions/monster-list-response-definition';

export default interface UseMonstersDefinition {
  data: MonsterListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: MonsterListResponseDefinition | null;
  search_text: string;
  set_search_text: StateSetter<string>;
  page: number;
  set_page: StateSetter<number>;
  game_map_id: number | null;
  set_game_map_id: (game_map_id: number | null) => void;
  category: MonsterListCategory;
  set_category: (category: MonsterListCategory) => void;
  location_type: number | null;
  set_location_type: (location_type: number | null) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
