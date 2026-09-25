import { resolveItemPosition } from './resolve-item-position';
import { EquippableItemWithBase } from '../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { getItemPositions } from '../../../../../reusable-components/item/utils/get-item-position';
import { InventoryPositionDefinition } from '../../../../character-sheet/partials/character-inventory/enums/equipment-positions';

export const isItemCompatibleWithPosition = (
  candidate: EquippableItemWithBase,
  targetPosition: InventoryPositionDefinition
): boolean => {
  const positions = getItemPositions(candidate);
  const resolvedTargetPosition = resolveItemPosition(targetPosition);

  if (positions === null) {
    return false;
  }

  if (resolvedTargetPosition === null) {
    return false;
  }

  return positions.includes(resolvedTargetPosition);
};
