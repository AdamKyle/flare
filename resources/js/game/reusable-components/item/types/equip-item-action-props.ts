import { ItemComparison } from '../../../api-definitions/items/item-comparison-details';
import EquipItemSelectionDefinition from '../definitions/equip-item-selection-definition';

export default interface EquipItemActionProps {
  comparison_details: ItemComparison;
  on_confirm_action: (selection: EquipItemSelectionDefinition) => void;
  on_close_equip_action?: () => void;
  is_processing: boolean;
}
