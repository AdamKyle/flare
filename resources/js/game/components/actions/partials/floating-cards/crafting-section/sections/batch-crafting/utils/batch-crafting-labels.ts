import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingEndReason } from '../enums/batch-crafting-end-reason';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';

const DISPOSITION_LABELS: Record<BatchCraftingDisposition, string> = {
  [BatchCraftingDisposition.KEEP]: 'Keep',
  [BatchCraftingDisposition.SELL]: 'Sell',
  [BatchCraftingDisposition.DESTROY]: 'Destroy',
};

const OUTPUT_DESTINATION_LABELS: Record<
  BatchCraftingOutputDestination,
  string
> = {
  [BatchCraftingOutputDestination.INVENTORY]: 'Inventory',
  [BatchCraftingOutputDestination.CRAFTED_ITEMS_SET]: 'Crafted Items Set',
};

const END_REASON_LABELS: Record<BatchCraftingEndReason, string> = {
  [BatchCraftingEndReason.COMPLETED_DURATION]: 'Time limit reached',
  [BatchCraftingEndReason.DIED]: 'Character died',
  [BatchCraftingEndReason.NO_GOLD]: 'Not enough Gold',
  [BatchCraftingEndReason.NO_INVENTORY_SPACE]: 'Inventory is full',
  [BatchCraftingEndReason.MAXED_OR_NOTHING_LEFT]: 'Nothing else can be crafted',
  [BatchCraftingEndReason.CANCELLED]: 'Cancelled',
  [BatchCraftingEndReason.FAILED]: 'Batch Crafting failed',
  [BatchCraftingEndReason.AMOUNT_REACHED]: 'Requested amount crafted',
  [BatchCraftingEndReason.BATCH_CRAFTING_SET_FULL]: 'Crafted Items Set is full',
};

export const dispositionLabel = (
  disposition: BatchCraftingDisposition
): string => DISPOSITION_LABELS[disposition];

export const outputDestinationLabel = (
  destination: BatchCraftingOutputDestination | null
): string | null => {
  if (!destination) {
    return null;
  }

  return OUTPUT_DESTINATION_LABELS[destination];
};

export const endReasonLabel = (endReason: BatchCraftingEndReason): string =>
  END_REASON_LABELS[endReason];
