import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const buildTransferItemOptions = (
  items: DropdownItem[],
  excludedId: number | null
): DropdownItem[] => items.filter((item) => item.value !== excludedId);
