import { EquippableItemWithBase } from '../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { InventoryItemTypes } from '../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';

export const isQuestItem = (
  item: EquippableItemWithBase | BaseQuestItemDefinition
): item is BaseQuestItemDefinition => item.type === InventoryItemTypes.QUEST;
