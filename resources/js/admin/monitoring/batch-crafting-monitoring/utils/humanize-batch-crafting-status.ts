import { BatchCraftingRunRow } from '../api/definitions/batch-crafting-monitoring-definition';

export default function humanizeBatchCraftingStatus(
  row: BatchCraftingRunRow
): string {
  if (row.cancelled_at) {
    return 'Cancelled';
  }

  if (row.completed_at) {
    return row.ended_reason ?? 'Completed';
  }

  return 'Running';
}
