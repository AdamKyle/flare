import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { CraftingBatchMode } from '../../enums/crafting-batch-mode';

export interface CraftExperienceProgressDefinition {
  craft_mode: CraftingBatchMode;
}

export default interface CraftExperienceRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: CraftExperienceProgressDefinition;
}
