import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';

export default interface CraftAmountMetricsProps {
  disposition: BatchCraftingDisposition;
  successful: number;
  failed: number;
  remaining: number | null;
  gold_spent: number;
  gold_gained: number;
  gold_left: number;
}
