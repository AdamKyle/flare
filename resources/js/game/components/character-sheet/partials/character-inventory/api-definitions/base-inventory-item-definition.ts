import { BaseItemDetails } from '../../../../../api-definitions/items/base-item-details';
import { InventoryPositionDefinition } from '../enums/equipment-positions';
import { InventoryItemTypes } from '../enums/inventory-item-types';

export default interface BaseInventoryItemDefinition extends BaseItemDetails {
  item_id: number;
  slot_id: number;
  ac: number;
  attack: number;
  position: InventoryPositionDefinition;
  usable: boolean;
  type: InventoryItemTypes;
}
