import { InventoryPositionDefinition } from '../../../../character-sheet/partials/character-inventory/enums/equipment-positions';
import { InventoryItemTypes } from '../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface EquippedItemDetailsProps extends SidePeekProps {
  character_id: number;
  slot_id: number;
  item_type: InventoryItemTypes;
  position: InventoryPositionDefinition;
  item_name: string;
  on_equipment_changed: () => void;
}
