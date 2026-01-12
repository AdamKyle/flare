import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';
import { InventoryPositionDefinition } from '../enums/equipment-positions';
import { InventoryItemTypes } from '../enums/inventory-item-types';

/**
 * Fetch equipped item for a given slot based on type.
 *
 * @param equippedItems
 * @param inventoryType
 * @param position
 */
export const fetchEquippedItemForSlot = (
  equippedItems: BaseInventoryItemDefinition[] | [],
  inventoryType: InventoryItemTypes | InventoryItemTypes[],
  position: InventoryPositionDefinition
): BaseInventoryItemDefinition | undefined => {
  if (!equippedItems) {
    return;
  }

  return equippedItems.find((equippedItem: BaseInventoryItemDefinition) => {
    if (Array.isArray(inventoryType)) {
      return (
        inventoryType.includes(equippedItem.type) &&
        equippedItem.position === position
      );
    }

    return (
      equippedItem.type === inventoryType && equippedItem.position === position
    );
  });
};
