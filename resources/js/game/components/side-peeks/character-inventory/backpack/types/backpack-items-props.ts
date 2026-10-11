import BackpackFilterDefinition from './backpack-filter-definition';
import { InventoryPositionDefinition } from '../../../../character-sheet/partials/character-inventory/enums/equipment-positions';
import GenericItemProps from '../../../components/items/types/generic-item-props';

export default interface BackpackItemsProps extends GenericItemProps {
  initial_search_text?: string;
  initial_filters?: BackpackFilterDefinition;
  target_position?: InventoryPositionDefinition;
  on_equipment_changed?: () => void;
}
