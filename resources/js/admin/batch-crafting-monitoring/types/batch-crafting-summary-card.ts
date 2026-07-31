import { BatchCraftingFilters } from '../api/definitions/batch-crafting-monitoring-definition';

export default interface BatchCraftingSummaryCard {
  label: string;
  value: number;
  filter: Partial<BatchCraftingFilters> | null;
}
