import { EquippableItemWithBase } from '../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import QuestItemOwnershipState from '../enums/quest-item-ownership-state';

export default interface GenericItemComponentProps {
  item: EquippableItemWithBase | BaseQuestItemDefinition;
  on_click?: (item: EquippableItemWithBase | BaseQuestItemDefinition) => void;
  is_selected?: boolean;
  on_item_selected?: (id: number, checked: boolean) => void;
  is_selection_disabled?: boolean;
  quest_item_ownership_state?: QuestItemOwnershipState;
}
