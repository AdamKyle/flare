import { ItemComparisonRow } from '../../../api-definitions/items/item-comparison-details';
import { InventoryItemTypes } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { ItemPositions } from '../enums/item-positions';

export default interface EquipItemActionProps {
  comparisonDetails: ItemComparisonRow[] | [];
  on_buy_and_replace: (
    position: ItemPositions,
    slot_id: number,
    type: InventoryItemTypes,
    item_to_buy_id: number
  ) => void;
  on_close_buy_and_equip: () => void;
}
