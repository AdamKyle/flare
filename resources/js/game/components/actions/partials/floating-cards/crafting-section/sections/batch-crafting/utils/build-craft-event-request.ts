import CraftEventRequestDefinition from '../api/definitions/craft-event-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';

export const buildCraftEventRequest = (): CraftEventRequestDefinition => ({
  batch_type: BatchCraftingType.CRAFT,
  disposition: BatchCraftingDisposition.KEEP,
  progress: {
    craft_mode: CraftingBatchMode.EVENT,
  },
});
