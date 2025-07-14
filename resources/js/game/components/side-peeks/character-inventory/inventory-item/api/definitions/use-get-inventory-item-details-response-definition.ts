import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ItemDetails from '../../../../../../api-definitions/items/item-details';

export default interface UseGetInventoryItemDetailsResponse {
  data: ItemDetails | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
