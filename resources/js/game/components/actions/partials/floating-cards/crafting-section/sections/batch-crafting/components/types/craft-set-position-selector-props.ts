import {
  CraftableItemCraftingType,
  CraftableItemSubtype,
} from '../../../crafting/api/definitions/craftable-item-query-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface CraftSetPositionSelectorProps {
  label: string;
  crafting_type: CraftableItemCraftingType;
  armour_type: CraftableItemSubtype | null;
  item_type: CraftableItemSubtype | null;
  selected_item: DropdownItem | null;
  on_select: (item: DropdownItem) => void;
}
