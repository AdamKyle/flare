import { match } from 'ts-pattern';

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
      let foundItemForPosition =
        inventoryType.includes(equippedItem.type) &&
        equippedItem.position === position;

      const oppositeHand = fetchOppositeHand(position);

      if (!foundItemForPosition) {
        foundItemForPosition =
          inventoryType.includes(equippedItem.type) &&
          equippedItem.position === oppositeHand;
      }

      return foundItemForPosition;
    }

    return (
      equippedItem.type === inventoryType && equippedItem.position === position
    );
  });
};

const fetchOppositeHand = (
  position: InventoryPositionDefinition
): InventoryPositionDefinition => {
  return match(position)
    .with(InventoryPositionDefinition.LEFT_HAND, () => {
      return InventoryPositionDefinition.RIGHT_HAND;
    })
    .with(InventoryPositionDefinition.RIGHT_HAND, () => {
      return InventoryPositionDefinition.LEFT_HAND;
    })
    .otherwise(() => position);
};
