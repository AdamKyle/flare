import { BatchCraftingSummary } from '../api/definitions/batch-crafting-monitoring-definition';

export const EMPTY_BATCH_CRAFTING_SUMMARY: BatchCraftingSummary = {
  total_runs: 0,
  active: 0,
  completed: 0,
  cancelled: 0,
  total_crafted: 0,
  total_failed: 0,
};
