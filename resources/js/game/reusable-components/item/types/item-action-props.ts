import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { ItemActions } from '../enums/item-actions';

export default interface ItemActionProps {
  item: EquippableItemWithBase;
  action_type: ItemActions;
  on_confirmation: (action: ItemActions) => void;
  on_cancel: () => void;
  processing: boolean;
}
