import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

/**
 * Convert a selected `Dropdown` option's value into the numeric id the
 * public Quest Information page's Map filter stores. `on_select` only ever
 * fires with an actually-chosen option (never an empty value); `on_clear`
 * is the separate callback that sets the filter back to `null`.
 */
export const parseNumberOption = (value: DropdownItem['value']): number =>
  typeof value === 'number' ? value : Number(value);
