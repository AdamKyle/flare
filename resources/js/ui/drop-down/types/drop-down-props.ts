import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface DropdownProps {
  items: DropdownItem[];
  on_select: (item: DropdownItem) => void;
  on_clear: () => void;
  all_click_outside?: boolean;
}
