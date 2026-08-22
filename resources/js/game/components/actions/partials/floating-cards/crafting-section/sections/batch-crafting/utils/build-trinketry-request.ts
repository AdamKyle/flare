import TrinketryRequestDefinition from '../api/definitions/trinketry-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { TrinketryBatchMode } from '../enums/trinketry-batch-mode';

export const buildTrinketryRequest = (
  disposition: BatchCraftingDisposition | null
): TrinketryRequestDefinition | null => {
  if (!disposition) {
    return null;
  }

  return {
    batch_type: BatchCraftingType.TRINKETRY,
    disposition,
    progress: {
      trinketry_mode: TrinketryBatchMode.EXPERIENCE,
    },
  };
};
