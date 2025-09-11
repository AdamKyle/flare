import { SelectedEquippableItemsOptions } from '../enums/selected-equippable-items-options';

export default interface BackPackSelectionActionsProps {
  on_action_bar_close: () => void;
  on_submit_action: () => void;
  action_type: SelectedEquippableItemsOptions;
  is_loading?: boolean;
}
