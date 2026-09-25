import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { ItemComparison } from '../../../api-definitions/items/item-comparison-details';
import EquipItemSelectionDefinition from '../definitions/equip-item-selection-definition';

export default interface ItemComparisonProps {
  comparisonDetails: ItemComparison;
  show_buy_and_replace?: boolean;
  is_purchasing: boolean;
  error_message?: AxiosErrorDefinition | null;
  on_buy_and_replace: (selection: EquipItemSelectionDefinition) => void;
}
