import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { CraftAndEnchantBatchMode } from '../../enums/craft-and-enchant-batch-mode';

export interface CraftAndEnchantAmountProgressDefinition {
  craft_enchant_mode: CraftAndEnchantBatchMode;
  specific_crafting_type: string;
  specific_item_id: number;
  prefix_id: number | null;
  suffix_id: number | null;
  craft_amount: number;
  output_destination?: BatchCraftingOutputDestination;
  listing_price?: number;
}

export default interface CraftAndEnchantAmountRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: CraftAndEnchantAmountProgressDefinition;
}
