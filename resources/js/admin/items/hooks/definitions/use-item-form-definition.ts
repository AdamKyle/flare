import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ItemFormDefinition from '../../api/definitions/item-form-definition';
import ItemFormOptionsDefinition from '../../api/definitions/item-form-options-definition';
import ItemFormErrorsDefinition from '../../definitions/item-form-errors-definition';
import ItemFormStateDefinition from '../../definitions/item-form-state-definition';

export default interface UseItemFormDefinition {
  form_state: ItemFormStateDefinition;
  update_field: <K extends keyof ItemFormStateDefinition>(
    field: K,
    value: ItemFormStateDefinition[K]
  ) => void;
  form_options: ItemFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: ItemFormErrorsDefinition;
  request_next: (step_index: number) => boolean;
  submit: () => Promise<ItemFormDefinition | null>;
}
