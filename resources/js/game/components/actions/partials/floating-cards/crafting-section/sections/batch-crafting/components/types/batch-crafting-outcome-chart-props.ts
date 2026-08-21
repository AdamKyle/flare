import BatchCraftingChartPointDefinition from '../../api/definitions/batch-crafting-chart-point-definition';

export default interface BatchCraftingOutcomeChartProps {
  chart_points: BatchCraftingChartPointDefinition[];
  processing_started_at: string | null;
}
