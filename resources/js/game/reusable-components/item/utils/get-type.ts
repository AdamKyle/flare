import { BaseItemDetails } from '../../../api-definitions/items/base-item-details';
import {
  InventoryItemTypes,
  weaponTypes,
} from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { ItemBaseTypes } from '../enums/item-base-type';
import { ItemBaseType } from '../types/item-base-type';

export const getType = (
  item: Pick<BaseItemDetails, 'type'>,
  armourPositions: InventoryItemTypes[]
): ItemBaseType | null => {
  if (armourPositions.includes(item.type)) {
    return ItemBaseTypes.Armour;
  }

  if (weaponTypes.includes(item.type)) {
    return ItemBaseTypes.Weapon;
  }

  switch (item.type) {
    case InventoryItemTypes.SPELL_HEALING:
    case InventoryItemTypes.SPELL_DAMAGE:
      return ItemBaseTypes.Spell;

    case InventoryItemTypes.RING:
      return ItemBaseTypes.Ring;

    case InventoryItemTypes.TRINKET:
      return ItemBaseTypes.Trinket;

    case InventoryItemTypes.ARTIFACT:
      return ItemBaseTypes.Artifact;

    default:
      return null;
  }
};
