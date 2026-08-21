import React, { ReactNode } from 'react';

import BatchCraftingCompletionSummaryProps from './types/batch-crafting-completion-summary-props';
import { BatchCraftingEndReason } from '../enums/batch-crafting-end-reason';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const buildSummaryMessage = (
  endReason: BatchCraftingEndReason,
  requested: number,
  completed: number,
  remaining: number
): string => {
  switch (endReason) {
    case BatchCraftingEndReason.AMOUNT_REACHED:
      return `Crafted all ${requested} requested items.`;
    case BatchCraftingEndReason.NO_INVENTORY_SPACE:
      return `Crafted ${completed} of ${requested}. ${remaining} requested items could not be crafted because your Inventory is full.`;
    case BatchCraftingEndReason.BATCH_CRAFTING_SET_FULL:
      return `Crafted ${completed} of ${requested}. ${remaining} requested items could not be crafted because your Crafted Items Set is full.`;
    case BatchCraftingEndReason.NO_GOLD:
      return `Crafted ${completed} of ${requested}. ${remaining} requested items could not be crafted because you do not have enough Gold.`;
    case BatchCraftingEndReason.CANCELLED:
      return `Batch Crafting was cancelled after ${completed} of ${requested} requested items were crafted.`;
    case BatchCraftingEndReason.FAILED:
      return `Batch Crafting stopped because of a server issue. ${completed} of ${requested} requested items were crafted.`;
    case BatchCraftingEndReason.DIED:
      return `Batch Crafting stopped because your character died. ${completed} of ${requested} requested items were crafted.`;
    case BatchCraftingEndReason.COMPLETED_DURATION:
      return `Batch Crafting reached its time limit after ${completed} of ${requested} requested items were crafted.`;
    case BatchCraftingEndReason.MAXED_OR_NOTHING_LEFT:
      return `Crafted ${completed} of ${requested}. The selected item is no longer available to craft.`;
    default:
      return `Batch Crafting ended. Crafted ${completed} of ${requested}.`;
  }
};

const BatchCraftingCompletionSummary = ({
  end_reason,
  requested,
  completed,
  remaining,
}: BatchCraftingCompletionSummaryProps): ReactNode => {
  const message = buildSummaryMessage(
    end_reason,
    requested ?? 0,
    completed,
    remaining ?? 0
  );

  const variant =
    end_reason === BatchCraftingEndReason.AMOUNT_REACHED
      ? AlertVariant.SUCCESS
      : AlertVariant.INFO;

  return <Alert variant={variant}>{message}</Alert>;
};

export default BatchCraftingCompletionSummary;
