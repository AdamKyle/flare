import React, { ReactNode } from 'react';

import CraftAmountProgressProps from './types/craft-amount-progress-props';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const destinationLabel = (
  destination: BatchCraftingOutputDestination
): string => {
  if (destination === BatchCraftingOutputDestination.CRAFTED_ITEMS_SET) {
    return 'Crafted Items Set';
  }

  return 'Inventory';
};

const CraftAmountProgress = ({
  requested_amount,
  completed_amount,
  output_destination,
  destination_capacity,
}: CraftAmountProgressProps): ReactNode => {
  const renderDestinationProgress = () => {
    if (!destination_capacity || !output_destination) {
      return null;
    }

    return (
      <ProgressBar
        label={destinationLabel(output_destination)}
        value={destination_capacity.current}
        max={destination_capacity.max}
        variant={ProgressBarVariant.ARTIC}
        value_label={`${formatNumberWithCommas(destination_capacity.current)} / ${formatNumberWithCommas(destination_capacity.max)}`}
      />
    );
  };

  return (
    <div className="space-y-3">
      <ProgressBar
        label="Batch Progress"
        value={completed_amount}
        max={requested_amount}
        variant={ProgressBarVariant.PRIMARY}
        value_label={`${formatNumberWithCommas(completed_amount)} / ${formatNumberWithCommas(requested_amount)}`}
      />

      {renderDestinationProgress()}
    </div>
  );
};

export default CraftAmountProgress;
