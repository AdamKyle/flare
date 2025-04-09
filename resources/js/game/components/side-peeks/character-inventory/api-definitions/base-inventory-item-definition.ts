import { BaseItemDetails } from '../../../../api-definitions/items/base-item-details';
import { InventoryPositionDefinition } from '../../../character-sheet/partials/character-inventory/enums/equipment-positions';
import { InventoryItemTypes } from '../../../character-sheet/partials/character-inventory/enums/inventory-item-types';

export default interface BaseInventoryItemDefinition extends BaseItemDetails {
  item_id: number;
  slot_id: number;
  ac: number;
  attack: number;
  position: InventoryPositionDefinition;
  usable: boolean;
  type: InventoryItemTypes;
  damages_kingdoms: boolean;
  kingdom_damage: number | null;
  lasts_for: number;
  can_stack: boolean;
  effect: string;
}
