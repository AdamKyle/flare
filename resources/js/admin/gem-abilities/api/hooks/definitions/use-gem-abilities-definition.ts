import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GemAbilityListDefinition from '../../definitions/gem-ability-list-definition';
import { GemAbilityListResponseDefinition } from '../../definitions/gem-ability-list-response-definition';

export default interface UseGemAbilitiesDefinition {
  data: GemAbilityListDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: GemAbilityListResponseDefinition | null;
  search_text: string;
  set_search_text: (value: string) => void;
  page: number;
  set_page: (page: number) => void;
  ability_type: string | null;
  set_ability_type: (ability_type: string | null) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
}
