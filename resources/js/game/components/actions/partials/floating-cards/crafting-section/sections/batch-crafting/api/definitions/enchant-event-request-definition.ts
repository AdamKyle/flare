import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { EnchantingBatchMode } from '../../enums/enchanting-batch-mode';

export interface EnchantEventProgressDefinition {
  enchant_mode: EnchantingBatchMode;
}

export default interface EnchantEventRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: EnchantEventProgressDefinition;
}
