import LabyrinthInventoryItemDefinition from '../api/definitions/labyrinth-inventory-item-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const buildTransferItemOptions = (
  inventory: LabyrinthInventoryItemDefinition[],
  excludedId: number | null
): DropdownItem[] =>
  inventory
    .filter((item) => item.id !== excludedId)
    .map((item) => ({ label: item.affix_name, value: item.id }));
