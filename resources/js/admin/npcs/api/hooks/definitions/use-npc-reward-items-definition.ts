import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { Dispatch, SetStateAction } from 'react';

import AdminQuestItemPresentationDefinition from '../../../../items/api/definitions/admin-quest-item-presentation-definition';

export default interface UseNpcRewardItemsDefinition {
  data: AdminQuestItemPresentationDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  page: number;
  set_page: Dispatch<SetStateAction<number>>;
  total_pages: number;
  total_records: number;
  refresh: () => void;
}
