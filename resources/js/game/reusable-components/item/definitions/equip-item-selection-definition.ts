import { InventoryItemTypes } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { ItemPositions } from '../enums/item-positions';

export default interface EquipItemSelectionDefinition {
  position: ItemPositions;
  slot_id: number;
  equip_type: InventoryItemTypes;
  item_id: number;
}
