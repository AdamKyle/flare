import { ItemActions } from '../../../../../reusable-components/item/enums/item-actions';

export default interface InventoryItemActionButtonProps {
  on_select_action: (action: ItemActions) => void;
  is_open: boolean;
  on_open_change: (isOpen: boolean) => void;
}
