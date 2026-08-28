import BatchCraftingLogsPage from '../../definitions/batch-crafting-logs-page-definition';
import {
  ActiveBatchCrafter,
  BatchCraftingChartPoint,
  BatchCraftingFilters,
  BatchCraftingRunRow,
  BatchCraftingSummary,
  Paginated,
} from '../../definitions/batch-crafting-monitoring-definition';

export default interface UseBatchCraftingApiDefinition {
  fetchBatchCraftingActive: () => Promise<ActiveBatchCrafter[]>;
  fetchBatchCraftingChart: (days: string) => Promise<BatchCraftingChartPoint[]>;
  fetchBatchCraftingLogs: (
    page: number,
    severity: string
  ) => Promise<BatchCraftingLogsPage>;
  fetchBatchCraftingRuns: (
    filters: BatchCraftingFilters,
    page: number
  ) => Promise<Paginated<BatchCraftingRunRow>>;
  fetchBatchCraftingSummary: (days: string) => Promise<BatchCraftingSummary>;
}
