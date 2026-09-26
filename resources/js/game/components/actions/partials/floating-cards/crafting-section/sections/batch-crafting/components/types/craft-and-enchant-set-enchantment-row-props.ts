import EnchantingAffixDefinition from '../../../enchanting/api/definitions/enchanting-affix-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface CraftAndEnchantSetEnchantmentRowProps {
  position_label: string;
  item_name: string;
  prefix_items: DropdownItem[];
  suffix_items: DropdownItem[];
  prefix_affixes: EnchantingAffixDefinition[];
  suffix_affixes: EnchantingAffixDefinition[];
  prefix_loading: boolean;
  prefix_search_text: string;
  on_prefix_search: (value: string) => void;
  prefix_can_load_more: boolean;
  prefix_is_loading_more: boolean;
  on_prefix_end_reached: () => void;
  suffix_loading: boolean;
  suffix_search_text: string;
  on_suffix_search: (value: string) => void;
  suffix_can_load_more: boolean;
  suffix_is_loading_more: boolean;
  on_suffix_end_reached: () => void;
  selected_prefix: DropdownItem | null;
  selected_suffix: DropdownItem | null;
  on_prefix_select: (item: DropdownItem) => void;
  on_suffix_select: (item: DropdownItem) => void;
}
