import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';
import { InventoryItemTypes } from '../enums/inventory-item-types';

/**
 * Fetch equipped item for a given slot based on type.
 *
 * @param equippedItems
 * @param inventoryType
 */
export const fetchEquippedItemForSlot = (
  equippedItems: BaseInventoryItemDefinition[] | [],
  inventoryType: InventoryItemTypes
): BaseInventoryItemDefinition | undefined => {
  if (!equippedItems) {
    return;
  }

  return equippedItems.find(
    (equippedItem: BaseInventoryItemDefinition) =>
      equippedItem.type === inventoryType
  );
};
