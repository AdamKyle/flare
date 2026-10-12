import { InventoryItemTypes } from '../../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import { BackpackEquipmentFilter } from '../../enums/backpack-equipment-filter';
import { BackpackRarityFilter } from '../../enums/backpack-rarity-filter';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseBackpackFiltersDefinition {
  equipmentFilter: BackpackEquipmentFilter | null;
  equipmentSubtype: InventoryItemTypes | null;
  rarityFilter: BackpackRarityFilter | null;
  selectEquipmentFilter: (item: DropdownItem) => void;
  clearEquipmentFilter: () => void;
  selectEquipmentSubtype: (item: DropdownItem) => void;
  clearEquipmentSubtype: () => void;
  selectRarityFilter: (item: DropdownItem) => void;
  clearRarityFilter: () => void;
}
