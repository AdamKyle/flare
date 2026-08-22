import EnchantEventRequestDefinition from '../api/definitions/enchant-event-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { EnchantingBatchMode } from '../enums/enchanting-batch-mode';

export const buildEnchantEventRequest = (): EnchantEventRequestDefinition => ({
  batch_type: BatchCraftingType.ENCHANT,
  disposition: BatchCraftingDisposition.KEEP,
  progress: {
    enchant_mode: EnchantingBatchMode.EVENT,
  },
});
