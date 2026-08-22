import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface CraftAndEnchantSetEnchantmentRowProps {
  position_label: string;
  item_name: string;
  prefix_items: DropdownItem[];
  suffix_items: DropdownItem[];
  selected_prefix: DropdownItem | null;
  selected_suffix: DropdownItem | null;
  on_prefix_select: (item: DropdownItem) => void;
  on_suffix_select: (item: DropdownItem) => void;
}
