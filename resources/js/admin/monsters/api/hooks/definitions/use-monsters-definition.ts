import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { StateSetter } from '../../../../../types/state-setter-type';
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
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
