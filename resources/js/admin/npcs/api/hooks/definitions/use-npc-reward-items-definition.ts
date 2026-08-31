import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import AdminQuestItemPresentationDefinition from '../../../../items/api/definitions/admin-quest-item-presentation-definition';

export default interface UseNpcRewardItemsDefinition {
  data: AdminQuestItemPresentationDefinition[];
  loading: boolean;
  is_loading_more: boolean;
  error: AxiosErrorDefinition | null;
  on_end_reached: () => void;
  refresh: () => void;
}
