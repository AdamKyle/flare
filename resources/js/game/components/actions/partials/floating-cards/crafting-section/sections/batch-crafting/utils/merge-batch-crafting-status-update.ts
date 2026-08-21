import BatchCraftingChartPointDefinition from '../api/definitions/batch-crafting-chart-point-definition';
import BatchCraftingStatusDefinition from '../api/definitions/batch-crafting-status-definition';
import BatchCraftingStatusUpdatedDefinition from '../api/definitions/batch-crafting-status-updated-definition';

const appendChartPointIfNew = (
  points: BatchCraftingChartPointDefinition[],
  point: BatchCraftingChartPointDefinition | null
): BatchCraftingChartPointDefinition[] => {
  if (!point) {
    return points;
  }

  const alreadyRecorded = points.some(
    (existingPoint) => existingPoint.occurred_at === point.occurred_at
  );

  if (alreadyRecorded) {
    return points;
  }

  return [...points, point];
};

export const mergeBatchCraftingStatusUpdate = (
  currentStatus: BatchCraftingStatusDefinition | null,
  update: BatchCraftingStatusUpdatedDefinition
): BatchCraftingStatusDefinition => {
  const updateStatus = update.status;
  const updateBatch = updateStatus.batch;

  // A runtime broadcast omits capabilities (null) rather than recalculating them, so the
  // last authoritative value is retained. Any status that does carry a real, non-null
  // capabilities object (the initial/reopen GET, or a later no-visible-batch update after
  // completion/dismissal) is itself authoritative and must replace the retained value.
  const capabilities =
    updateStatus.capabilities ?? currentStatus?.capabilities ?? null;

  if (!updateBatch) {
    return { ...updateStatus, capabilities };
  }

  const currentBatch = currentStatus?.batch ?? null;

  const previousChartPoints =
    currentBatch !== null && currentBatch.id === updateBatch.id
      ? currentBatch.chart_points
      : updateBatch.chart_points;

  return {
    ...updateStatus,
    capabilities,
    batch: {
      ...updateBatch,
      chart_points: appendChartPointIfNew(
        previousChartPoints,
        update.chart_point
      ),
    },
  };
};

export const mergeBatchCraftingStatusUpdates = (
  status: BatchCraftingStatusDefinition,
  updates: BatchCraftingStatusUpdatedDefinition[]
): BatchCraftingStatusDefinition => {
  return updates.reduce(
    (mergedStatus, update) =>
      mergeBatchCraftingStatusUpdate(mergedStatus, update),
    status
  );
};
