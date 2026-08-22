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
    gold_dust_spent: 0,
    shards_spent: 0,
    copper_coins_spent: 0,
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
      gold_dust_spent: point.gold_dust_spent,
      shards_spent: point.shards_spent,
      copper_coins_spent: point.copper_coins_spent,
    };
  });

  return [baselinePoint, ...attemptPoints];
};
