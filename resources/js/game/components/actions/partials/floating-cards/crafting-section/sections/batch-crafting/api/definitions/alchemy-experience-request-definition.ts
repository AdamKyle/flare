import { AlchemyBatchMode } from '../../enums/alchemy-batch-mode';
import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../../enums/batch-crafting-type';

export interface AlchemyExperienceProgressDefinition {
  alchemy_mode: AlchemyBatchMode.EXPERIENCE;
  listing_price?: number;
}

export default interface AlchemyExperienceRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: AlchemyExperienceProgressDefinition;
}
