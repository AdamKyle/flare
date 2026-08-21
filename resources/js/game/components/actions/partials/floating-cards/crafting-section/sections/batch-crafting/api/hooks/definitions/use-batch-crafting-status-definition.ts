import BatchCraftingStatusDefinition from '../../definitions/batch-crafting-status-definition';

export default interface UseBatchCraftingStatusDefinition {
  status: BatchCraftingStatusDefinition | null;
  loading: boolean;
  error: string | null;
}
