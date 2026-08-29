import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ItemDetailDefinition from '../../definitions/item-detail-definition';

export default interface UseItemDetailDefinition {
  item: ItemDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
