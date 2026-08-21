import { BatchCraftingBatchStatusDefinition } from '../api/definitions/batch-crafting-status-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';

const resolveRetainedDestinationLabel = (
  batch: BatchCraftingBatchStatusDefinition
): string | null => {
  if (batch.disposition !== BatchCraftingDisposition.KEEP) {
    return null;
  }

  if (batch.output_destination === BatchCraftingOutputDestination.INVENTORY) {
    return `Open ${batch.current_item_name} in your Backpack`;
  }

  if (
    batch.destination_set_id === null ||
    batch.destination_set_name === null
  ) {
    return null;
  }

  if (
    batch.output_destination ===
    BatchCraftingOutputDestination.CRAFTED_ITEMS_SET
  ) {
    return `Open ${batch.current_item_name} in your Crafted Items Set`;
  }

  if (
    batch.output_destination === BatchCraftingOutputDestination.INVENTORY_SET
  ) {
    return `Open ${batch.current_item_name} in ${batch.destination_set_name}`;
  }

  return null;
};

/**
 * The accessible label for the current Batch Crafting item, when it is known.
 *
 * When the item's actual retained destination is factually known (Backpack or a
 * named Set), the label names that destination. Otherwise it falls back to the
 * generic read-only Item Details flow, which is always factually available for a
 * known item id (Sell/Destroy/Keep Best dispositions never expose a retained
 * destination for the current item).
 */
export const getBatchCraftedItemLinkLabel = (
  batch: BatchCraftingBatchStatusDefinition
): string | null => {
  if (batch.current_item_id === null || batch.current_item_name === null) {
    return null;
  }

  return (
    resolveRetainedDestinationLabel(batch) ??
    `View item details for ${batch.current_item_name}`
  );
};
