import { ItemTypeToView } from '../../../components/items/enums/item-type-to-view';

export default interface InventoryItemProps {
  slot_id: number;
  character_id: number;
  type_of_item: ItemTypeToView;
  close_item_view: () => void;
}
