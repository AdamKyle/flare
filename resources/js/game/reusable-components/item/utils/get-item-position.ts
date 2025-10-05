import { getType } from './get-type';
import { isTwoHandedType } from './item-comparison';
import { BaseItemDetails } from '../../../api-definitions/items/base-item-details';
import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { armourPositions } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { ItemBaseTypes } from '../enums/item-base-type';
import { ItemPositions } from '../enums/item-positions';

export const getItemPositions = (
  item: BaseItemDetails | EquippableItemWithBase
) => {
  const itemType = getType(item, armourPositions);
  const isTwoHanded = isTwoHandedType(item.type);

  if (isTwoHanded) {
    return ItemPositions.LEFT_HAND;
  }

  if (itemType === ItemBaseTypes.Weapon) {
    return [ItemPositions.LEFT_HAND, ItemPositions.RIGHT_HAND];
  }

  if (itemType === ItemBaseTypes.Ring) {
    return [ItemPositions.RING_ONE, ItemPositions.RING_TWO];
  }

  if (itemType === ItemBaseTypes.Spell) {
    return [ItemPositions.SPELL_ONE, ItemPositions.SPELL_TWO];
  }

  if (itemType === ItemBaseTypes.Armour) {
    const positions = [
      ItemPositions.BODY,
      ItemPositions.HELMET,
      ItemPositions.FEET,
      ItemPositions.GLOVES,
      ItemPositions.LEGGINGS,
      ItemPositions.SLEEVES,
    ];

    return positions.find(
      (position) => (item.type as string) === (position as string)
    );
  }

  return [];
};
