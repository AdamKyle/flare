import { ItemTypeToView } from '../../../components/items/enums/item-type-to-view';

export default interface InventoryItemProps {
  item_id: number;
  character_id: number;
  type_of_item: ItemTypeToView;
}
