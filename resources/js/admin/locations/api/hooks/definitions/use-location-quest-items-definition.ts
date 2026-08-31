import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import AdminQuestItemPresentationDefinition from '../../../../items/api/definitions/admin-quest-item-presentation-definition';
import { LocationDropModeDefinition } from '../../definitions/location-quest-items-response-definition';

export default interface UseLocationQuestItemsDefinition {
  data: AdminQuestItemPresentationDefinition[];
  loading: boolean;
  is_loading_more: boolean;
  error: AxiosErrorDefinition | null;
  drop_mode: LocationDropModeDefinition | null;
  on_end_reached: () => void;
  refresh: () => void;
}
