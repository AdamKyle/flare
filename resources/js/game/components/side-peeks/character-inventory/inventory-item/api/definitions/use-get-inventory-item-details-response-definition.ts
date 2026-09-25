import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { EquippableItemDetailsDefinition } from '../../../../../../api-definitions/items/equippable-item-definitions/equippable-item-details-definition';
import BaseQuestItemDefinition from '../../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';

export default interface UseGetInventoryItemDetailsResponse {
  data: EquippableItemDetailsDefinition | BaseQuestItemDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
  refetch: () => Promise<void>;
}
