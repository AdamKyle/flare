import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { ItemActions } from '../enums/item-actions';

export default interface ItemActionProps {
  item: EquippableItemWithBase;
  action_type: ItemActions;
  on_action: (successMessage: string) => void;
  on_cancel: () => void;
}
