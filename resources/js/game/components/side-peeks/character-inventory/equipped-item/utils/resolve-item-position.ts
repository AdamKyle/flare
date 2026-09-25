import { ItemPositions } from '../../../../../reusable-components/item/enums/item-positions';
import { InventoryPositionDefinition } from '../../../../character-sheet/partials/character-inventory/enums/equipment-positions';

export const resolveItemPosition = (
  position: InventoryPositionDefinition
): ItemPositions | null => {
  switch (position) {
    case InventoryPositionDefinition.LEFT_HAND:
      return ItemPositions.LEFT_HAND;
    case InventoryPositionDefinition.RIGHT_HAND:
      return ItemPositions.RIGHT_HAND;
    case InventoryPositionDefinition.BODY:
      return ItemPositions.BODY;
    case InventoryPositionDefinition.LEGGINGS:
      return ItemPositions.LEGGINGS;
    case InventoryPositionDefinition.FEET:
      return ItemPositions.FEET;
    case InventoryPositionDefinition.SLEEVES:
      return ItemPositions.SLEEVES;
    case InventoryPositionDefinition.HELMET:
      return ItemPositions.HELMET;
    case InventoryPositionDefinition.GLOVES:
      return ItemPositions.GLOVES;
    case InventoryPositionDefinition.RING_ONE:
      return ItemPositions.RING_ONE;
    case InventoryPositionDefinition.RING_TWO:
      return ItemPositions.RING_TWO;
    case InventoryPositionDefinition.SPELL_ONE:
      return ItemPositions.SPELL_ONE;
    case InventoryPositionDefinition.SPELL_TWO:
      return ItemPositions.SPELL_TWO;
    case InventoryPositionDefinition.TRINKET:
      return ItemPositions.TRINKET;
    case InventoryPositionDefinition.ARTIFACT:
      return ItemPositions.ARTIFACT;
    case InventoryPositionDefinition.SHIELD:
    default:
      return null;
  }
};
