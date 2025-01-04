import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';
import { InventoryItemTypes } from '../enums/inventory-item-types';

type PartialInventoryItemTypes =
  | InventoryItemTypes.BODY
  | InventoryItemTypes.FEET
  | InventoryItemTypes.LEGGINGS
  | InventoryItemTypes.HELMET
  | InventoryItemTypes.GLOVES
  | InventoryItemTypes.SLEEVES;

/**
 * Fetch equipped armour.
 *
 * @param equippedItems
 * @param inventoryType
 */
export const fetchEquippedArmour = (
  equippedItems: BaseInventoryItemDefinition[] | [],
  inventoryType: PartialInventoryItemTypes
): BaseInventoryItemDefinition | undefined => {
  return equippedItems.find(
    (equippedItem: BaseInventoryItemDefinition) =>
      equippedItem.type === inventoryType
  );
};
