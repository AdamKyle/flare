import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { ItemComparison } from '../../../../../../api-definitions/items/item-comparison-details';

export default interface UseGetInventoryItemComparisonDefinition {
  loading: boolean;
  data: ItemComparison | null;
  error: AxiosErrorDefinition | null;
}
