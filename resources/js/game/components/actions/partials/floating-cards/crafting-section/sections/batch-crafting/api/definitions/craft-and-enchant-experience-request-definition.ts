import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { CraftAndEnchantBatchMode } from '../../enums/craft-and-enchant-batch-mode';

export interface CraftAndEnchantExperienceProgressDefinition {
  craft_enchant_mode: CraftAndEnchantBatchMode;
  listing_price?: number;
}

export default interface CraftAndEnchantExperienceRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: CraftAndEnchantExperienceProgressDefinition;
}
