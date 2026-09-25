import { getType } from './get-type';
import { BaseItemDetails } from '../../../api-definitions/items/base-item-details';
import {
  armourPositions,
  InventoryItemTypes,
} from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { ItemBaseTypes } from '../enums/item-base-type';
import { ItemPositions } from '../enums/item-positions';

export const getItemPositions = (
  item: Pick<BaseItemDetails, 'type'>
): ItemPositions[] | null => {
  const itemType = getType(item, armourPositions);

  if (itemType === ItemBaseTypes.Weapon) {
    return [ItemPositions.LEFT_HAND, ItemPositions.RIGHT_HAND];
  }

  if (itemType === ItemBaseTypes.Ring) {
    return [ItemPositions.RING_ONE, ItemPositions.RING_TWO];
  }

  if (itemType === ItemBaseTypes.Spell) {
    return [ItemPositions.SPELL_ONE, ItemPositions.SPELL_TWO];
  }

  if (itemType === ItemBaseTypes.Trinket) {
    return [ItemPositions.TRINKET];
  }

  if (itemType === ItemBaseTypes.Artifact) {
    return [ItemPositions.ARTIFACT];
  }

  if (itemType === ItemBaseTypes.Armour) {
    switch (item.type) {
      case InventoryItemTypes.BODY:
        return [ItemPositions.BODY];
      case InventoryItemTypes.HELMET:
        return [ItemPositions.HELMET];
      case InventoryItemTypes.FEET:
        return [ItemPositions.FEET];
      case InventoryItemTypes.GLOVES:
        return [ItemPositions.GLOVES];
      case InventoryItemTypes.LEGGINGS:
        return [ItemPositions.LEGGINGS];
      case InventoryItemTypes.SLEEVES:
        return [ItemPositions.SLEEVES];
      default:
        return null;
    }
  }

  return null;
};
