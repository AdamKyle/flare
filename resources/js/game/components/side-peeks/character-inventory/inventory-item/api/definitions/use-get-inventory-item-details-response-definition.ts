import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { EquippableItemWithBase } from '../../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

export default interface UseGetInventoryItemDetailsResponse {
  data: EquippableItemWithBase | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
