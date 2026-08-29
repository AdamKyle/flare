import ItemFormDefinition from '../api/definitions/item-form-definition';

export default interface ItemFormContentProps {
  item_id: number | null;
  on_saved: (item: ItemFormDefinition) => void;
  on_cancel: () => void;
  embedded?: boolean;
}
