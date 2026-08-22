import CraftAndEnchantOutputSelection from './craft-and-enchant-output-selection';
import { CraftSetPosition } from '../enums/craft-set-position';

export interface CraftAndEnchantSetSelectedItem {
  item_id: number;
  item_name: string;
  crafting_type: string;
}

export default interface CraftAndEnchantSetSelection {
  output_selection: CraftAndEnchantOutputSelection;
  selected_items: Partial<
    Record<CraftSetPosition, CraftAndEnchantSetSelectedItem>
  >;
}
