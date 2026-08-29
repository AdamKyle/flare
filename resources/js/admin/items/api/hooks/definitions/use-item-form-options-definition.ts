import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ItemFormOptionsDefinition from '../../definitions/item-form-options-definition';

export default interface UseItemFormOptionsDefinition {
  form_options: ItemFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
