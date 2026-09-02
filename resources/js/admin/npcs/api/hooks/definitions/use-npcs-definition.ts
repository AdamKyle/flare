import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { Dispatch, SetStateAction } from 'react';

import { NpcType } from '../../../enums/npc-type';
import NpcListDefinition from '../../definitions/npc-list-definition';
import { NpcListResponseDefinition } from '../../definitions/npc-list-response-definition';

export default interface UseNpcsDefinition {
  data: NpcListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: NpcListResponseDefinition | null;
  search_text: string;
  set_search_text: Dispatch<SetStateAction<string>>;
  page: number;
  set_page: Dispatch<SetStateAction<number>>;
  game_map_id: number | null;
  set_game_map_id: (game_map_id: number | null) => void;
  type: NpcType | null;
  set_type: (type: NpcType | null) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
