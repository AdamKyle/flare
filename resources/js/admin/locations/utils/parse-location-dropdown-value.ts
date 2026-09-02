import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

/**
 * Convert a selected `Dropdown` option's value into the numeric id the
 * Location list filter state stores. `on_select` only ever fires with an
 * actually-chosen option (never an empty value); `on_clear` is the separate
 * callback that sets a field back to `null`.
 */
export const parseNumberOption = (value: DropdownItem['value']): number =>
  typeof value === 'number' ? value : Number(value);
