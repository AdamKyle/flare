import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { EquippableItemWithBase } from '../../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';

export default interface UseGetInventoryItemDetailsResponse {
  data: EquippableItemWithBase | BaseQuestItemDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
