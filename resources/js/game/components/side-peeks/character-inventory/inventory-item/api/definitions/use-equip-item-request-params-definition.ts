import { ItemPositions } from '../../../../../../reusable-components/item/enums/item-positions';
import { InventoryPositionDefinition } from '../../../../../character-sheet/partials/character-inventory/enums/equipment-positions';
import { InventoryItemTypes } from '../../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';

export default interface UseEquipItemRequestParamsDefinition {
  position: ItemPositions | InventoryPositionDefinition | null;
  slot_id: number;
  equip_type: InventoryItemTypes | null;
}
