import { BatchCraftingType } from '../enums/batch-crafting-type';

export const buildBatchCraftingRunningSectionKey = (
  batchType: BatchCraftingType,
  mode: string
): string => `${batchType}:${mode}`;
