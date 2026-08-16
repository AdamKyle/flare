import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { CraftingBatchMode } from '../../enums/crafting-batch-mode';

export interface CraftAmountProgressDefinition {
  craft_mode: CraftingBatchMode;
  specific_crafting_type: string;
  specific_item_id: number;
  craft_amount: number;
  output_destination?: BatchCraftingOutputDestination;
}

export default interface CraftAmountRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: CraftAmountProgressDefinition;
}
