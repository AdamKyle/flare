import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { TrinketryBatchMode } from '../../enums/trinketry-batch-mode';

export interface TrinketryProgressDefinition {
  trinketry_mode: TrinketryBatchMode.EXPERIENCE;
}

export default interface TrinketryRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: TrinketryProgressDefinition;
}
