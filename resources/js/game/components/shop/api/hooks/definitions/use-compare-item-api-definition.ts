import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ItemComparisonDetails from '../../../../../api-definitions/items/item-comparison-details';

export default interface UseCompareItemApiDefinition {
  loading: boolean;
  data: ItemComparisonDetails | null;
  error: AxiosErrorDefinition | null;
}
