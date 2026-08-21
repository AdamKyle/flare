import { BatchCraftingEndReason } from '../../enums/batch-crafting-end-reason';

export default interface BatchCraftingCompletionSummaryProps {
  end_reason: BatchCraftingEndReason;
  requested: number | null;
  completed: number;
  remaining: number | null;
}
