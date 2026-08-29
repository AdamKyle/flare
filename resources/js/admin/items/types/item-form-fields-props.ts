import ItemFormOptionsDefinition from '../api/definitions/item-form-options-definition';
import ItemFormErrorsDefinition from '../definitions/item-form-errors-definition';
import ItemFormStateDefinition from '../definitions/item-form-state-definition';

export default interface ItemFormFieldsProps {
  state: ItemFormStateDefinition;
  errors: ItemFormErrorsDefinition;
  form_options: ItemFormOptionsDefinition;
  on_change: <K extends keyof ItemFormStateDefinition>(
    field: K,
    value: ItemFormStateDefinition[K]
  ) => void;
}
