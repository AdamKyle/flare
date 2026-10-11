import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';
import { InventoryPositionDefinition } from '../enums/equipment-positions';
import { InventoryItemTypes } from '../enums/inventory-item-types';

export default interface EquippedSlotsProps {
  equipped_items: BaseInventoryItemDefinition[];
  on_open_item_details: (equippedItem: BaseInventoryItemDefinition) => void;
  on_open_empty_slot: (
    position: InventoryPositionDefinition,
    itemTypes: InventoryItemTypes[]
  ) => void;
}
