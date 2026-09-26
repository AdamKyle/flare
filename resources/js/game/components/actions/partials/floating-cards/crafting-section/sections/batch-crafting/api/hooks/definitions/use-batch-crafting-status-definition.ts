import BatchCraftingStatusDefinition from '../../definitions/batch-crafting-status-definition';

export default interface UseBatchCraftingStatusDefinition {
  status: BatchCraftingStatusDefinition | null;
  loading: boolean;
  error: string | null;
  live_update_count: number;
  refresh_status: () => Promise<void>;
}
