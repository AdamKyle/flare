import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const parseNumberOption = (value: DropdownItem['value']): number =>
  typeof value === 'number' ? value : Number(value);
