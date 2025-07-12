import ItemDetails from '../../../api-definitions/items/item-details';
import { InventoryItemTypes } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { ItemBaseTypes } from '../enums/item-base-type';
import { ItemBaseType } from '../types/item-base-type';

export const getType = (
  item: ItemDetails,
  armourPositions: InventoryItemTypes[]
): ItemBaseType => {
  if (armourPositions.includes(item.type as InventoryItemTypes)) {
    return ItemBaseTypes.Armour;
  }

  switch (item.type) {
    case InventoryItemTypes.SPELL_HEALING:
    case InventoryItemTypes.SPELL_DAMAGE:
      return ItemBaseTypes.Spell;

    case InventoryItemTypes.RING:
      return ItemBaseTypes.Ring;

    default:
      return ItemBaseTypes.Weapon;
  }
};
