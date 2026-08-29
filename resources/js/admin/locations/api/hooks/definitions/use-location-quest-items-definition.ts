import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { Dispatch, SetStateAction } from 'react';

import AdminQuestItemPresentationDefinition from '../../../../items/api/definitions/admin-quest-item-presentation-definition';
import { LocationDropModeDefinition } from '../../definitions/location-quest-items-response-definition';

export default interface UseLocationQuestItemsDefinition {
  data: AdminQuestItemPresentationDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  drop_mode: LocationDropModeDefinition | null;
  page: number;
  set_page: Dispatch<SetStateAction<number>>;
  total_pages: number;
  total_records: number;
  refresh: () => void;
}
