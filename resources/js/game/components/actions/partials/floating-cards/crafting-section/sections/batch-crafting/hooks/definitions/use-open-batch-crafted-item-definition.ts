import { BatchCraftingBatchStatusDefinition } from '../../api/definitions/batch-crafting-status-definition';

export default interface UseOpenBatchCraftedItemDefinition {
  openBatchCraftedItem: (batch: BatchCraftingBatchStatusDefinition) => void;
}
