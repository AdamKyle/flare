import BatchCraftingChartPointDefinition from '../api/definitions/batch-crafting-chart-point-definition';
import BatchCraftingChartDataPointDefinition from '../components/definitions/batch-crafting-chart-data-point-definition';

export const buildBatchCraftingChartData = (
  processingStartedAt: string | null,
  chartPoints: BatchCraftingChartPointDefinition[]
): BatchCraftingChartDataPointDefinition[] => {
  if (!processingStartedAt || chartPoints.length === 0) {
    return [];
  }

  const processingStartedAtMs = new Date(processingStartedAt).getTime();

  const baselinePoint: BatchCraftingChartDataPointDefinition = {
    elapsed_seconds: 0,
    successful: 0,
    failed: 0,
    gold_spent: 0,
    gold_gained: 0,
  };

  const attemptPoints = chartPoints.map((point) => {
    const occurredAtMs = new Date(point.occurred_at).getTime();
    const elapsedSeconds = Math.max(
      0,
      (occurredAtMs - processingStartedAtMs) / 1000
    );

    return {
      elapsed_seconds: elapsedSeconds,
      successful: point.successful,
      failed: point.failed,
      gold_spent: point.gold_spent,
      gold_gained: point.gold_gained,
    };
  });

  return [baselinePoint, ...attemptPoints];
};
