import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { EquippableItemWithBase } from '../../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { ItemComparison } from '../../../../../../api-definitions/items/item-comparison-details';

export default interface UseGetInventoryItemComparisonDefinition {
  loading: boolean;
  data: ItemComparison<EquippableItemWithBase> | null;
  error: AxiosErrorDefinition | null;
}
