import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ItemFormDefinition from '../../definitions/item-form-definition';
import ItemRequestDefinition from '../../definitions/item-request-definition';

export default interface UseSaveItemDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    item_id: number | null,
    payload: ItemRequestDefinition
  ) => Promise<ItemFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
