import React, { ReactNode } from 'react';

import CraftingInventoryProgressProps from './types/crafting-inventory-progress-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const CraftingInventoryProgress = ({
  inventory_count,
}: CraftingInventoryProgressProps): ReactNode => {
  const valueLabel = `${formatNumberWithCommas(inventory_count.current_count)}/${formatNumberWithCommas(inventory_count.max_inventory)}`;

  return (
    <ProgressBar
      label="Current Inventory Count"
      value={inventory_count.current_count}
      max={inventory_count.max_inventory}
      variant={ProgressBarVariant.ARTIC}
      value_label={valueLabel}
    />
  );
};

export default CraftingInventoryProgress;
