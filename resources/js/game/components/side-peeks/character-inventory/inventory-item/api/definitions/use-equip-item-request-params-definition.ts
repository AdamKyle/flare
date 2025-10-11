import { ItemPositions } from '../../../../../../reusable-components/item/enums/item-positions';
import { InventoryItemTypes } from '../../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';

export default interface UseEquipItemRequestParamsDefinition {
  position: ItemPositions | null;
  slot_id: number;
  equip_type: InventoryItemTypes | null;
}
