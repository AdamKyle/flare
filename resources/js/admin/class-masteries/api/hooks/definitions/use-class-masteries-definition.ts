import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassMasteryListDefinition from '../../definitions/class-mastery-list-definition';
import { ClassMasteryListResponseDefinition } from '../../definitions/class-mastery-list-response-definition';

export default interface UseClassMasteriesDefinition {
  data: ClassMasteryListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: ClassMasteryListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  game_class_id: number | null;
  set_game_class_id: (game_class_id: number | null) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
