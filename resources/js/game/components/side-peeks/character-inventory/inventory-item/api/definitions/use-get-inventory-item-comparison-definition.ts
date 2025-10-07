import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { ItemComparisonRow } from '../../../../../../api-definitions/items/item-comparison-details';

export default interface UseGetInventoryItemComparisonDefinition {
  loading: boolean;
  data: ItemComparisonRow[] | [] | null;
  error: AxiosErrorDefinition | null;
}
