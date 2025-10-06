import { ItemPositions } from '../../../../../reusable-components/item/enums/item-positions';
import { InventoryItemTypes } from '../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';

export default interface UsePurchaseAndReplaceApiRequestDefinition {
  position: ItemPositions | null;
  slot_id: number;
  equip_type: InventoryItemTypes | null;
  item_id_to_buy: number;
}
