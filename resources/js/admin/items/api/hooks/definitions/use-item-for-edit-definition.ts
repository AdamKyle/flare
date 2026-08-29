import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ItemFormDefinition from '../../definitions/item-form-definition';

export default interface UseItemForEditDefinition {
  item: ItemFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
