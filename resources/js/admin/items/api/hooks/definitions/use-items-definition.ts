import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { Dispatch, SetStateAction } from 'react';

import ItemDefinition from '../../definitions/item-definition';
import { ItemListResponseDefinition } from '../../definitions/item-list-response-definition';
import { ItemProfile } from '../../../enums/item-profile';

export default interface UseItemsDefinition {
  data: ItemDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  response: ItemListResponseDefinition | null;
  search_text: string;
  set_search_text: Dispatch<SetStateAction<string>>;
  page: number;
  set_page: Dispatch<SetStateAction<number>>;
  profile: ItemProfile;
  set_profile: (profile: ItemProfile) => void;
  sort_key: string;
  sort_direction: 'asc' | 'desc';
  set_sort: (sort_key: string) => void;
  refresh_first_page: () => void;
}
