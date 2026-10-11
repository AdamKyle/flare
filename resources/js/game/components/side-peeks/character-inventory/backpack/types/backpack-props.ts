import BackpackFilterDefinition from './backpack-filter-definition';
import { InventoryPositionDefinition } from '../../../../character-sheet/partials/character-inventory/enums/equipment-positions';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface BackpackProps extends SidePeekProps {
  initial_search_text?: string;
  initial_filters?: BackpackFilterDefinition;
  target_position?: InventoryPositionDefinition;
  on_equipment_changed?: () => void;
}
