import ItemFormStateDefinition from './item-form-state-definition';

type ItemFormErrorsDefinition = Partial<
  Record<keyof ItemFormStateDefinition, string>
>;

export default ItemFormErrorsDefinition;
