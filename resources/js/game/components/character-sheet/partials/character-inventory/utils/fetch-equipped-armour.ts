import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';
import { InventoryItemTypes } from '../enums/inventory-item-types';

type PartialInventoryItemTypes =
  | InventoryItemTypes.BODY
  | InventoryItemTypes.FEET
  | InventoryItemTypes.LEGGINGS
  | InventoryItemTypes.HELMET
  | InventoryItemTypes.GLOVES
  | InventoryItemTypes.SLEEVES;

/**
 * Fetch equipped item for a given slot based on type.
 *
 * @param equippedItems
 * @param inventoryType
 */
export const fetchEquippedItemForSlot = (
  equippedItems: BaseInventoryItemDefinition[] | [],
  inventoryType: PartialInventoryItemTypes
): BaseInventoryItemDefinition | undefined => {
  if (!equippedItems) {
    return;
  }

  return equippedItems.find(
    (equippedItem: BaseInventoryItemDefinition) =>
      equippedItem.type === inventoryType
  );
};
