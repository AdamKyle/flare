import BatchCraftingChartPointDefinition from './batch-crafting-chart-point-definition';
import BatchCraftingStatusDefinition from './batch-crafting-status-definition';

export default interface BatchCraftingStatusUpdatedDefinition {
  user_id: number;
  occurred_at: string;
  status: BatchCraftingStatusDefinition;
  chart_point: BatchCraftingChartPointDefinition | null;
}
