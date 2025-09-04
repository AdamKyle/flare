import { EquippableItemWithBase } from '../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';

export default interface BackpackItemProps {
  item: EquippableItemWithBase | BaseQuestItemDefinition;
  on_click?: (item: EquippableItemWithBase | BaseQuestItemDefinition) => void;
  is_selected?: boolean;
  on_item_selected?: (id: number, checked: boolean) => void;
}
