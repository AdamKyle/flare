import CraftExperienceRequestDefinition from '../api/definitions/craft-experience-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';

export const buildCraftExperienceRequest = (
  disposition: BatchCraftingDisposition
): CraftExperienceRequestDefinition => ({
  batch_type: BatchCraftingType.CRAFT,
  disposition,
  progress: {
    craft_mode: CraftingBatchMode.EXPERIENCE,
  },
});
