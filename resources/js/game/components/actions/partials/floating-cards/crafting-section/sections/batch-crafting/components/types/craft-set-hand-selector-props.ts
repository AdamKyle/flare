import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface CraftSetHandSelectorProps {
  label: string;
  selected_item: DropdownItem | null;
  on_select: (item: DropdownItem | null) => void;
}
