import React, { ReactNode } from 'react';

import SelectedCraftableItemDetails from './selected-craftable-item-details';
import CraftActionPanelProps from './types/craft-action-panel-props';
import { progressFillClass } from '../utils/progress-fill-class';

import ProgressButton from 'ui/buttons/button-progress';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CraftActionPanel = ({
  selectedItem,
  isCrafting,
  isCraftingDisabled,
  inventoryIsFull,
  isTimeoutActive,
  formattedRemaining,
  progress,
  onCraft,
}: CraftActionPanelProps): ReactNode => {
  if (!selectedItem) {
    return null;
  }

  const displayedProgress = isCrafting ? 100 : progress;

  const renderTimeoutMessage = () => {
    if (!isTimeoutActive) {
      return null;
    }

    return (
      <p
        className="text-mango-tango-700 dark:text-mango-tango-300 mt-2 text-sm"
        role="status"
        aria-live="polite"
      >
        You can craft again in {formattedRemaining}.
      </p>
    );
  };

  const renderInventoryFullMessage = () => {
    if (!inventoryIsFull) {
      return null;
    }

    return (
      <p className="mt-2 text-sm text-rose-600 dark:text-rose-400">
        Your inventory is full.
      </p>
    );
  };

  return (
    <div className="rounded-md border border-gray-400 p-3 dark:border-gray-600">
      <SelectedCraftableItemDetails item={selectedItem} />
      <ProgressButton
        label={isCrafting ? 'Crafting...' : 'Craft Item'}
        on_click={onCraft}
        variant={ButtonVariant.SUCCESS}
        progress={displayedProgress}
        disabled={isCrafting || isCraftingDisabled || inventoryIsFull}
        additional_css="mt-3 w-full"
        progress_fill_class={progressFillClass(displayedProgress)}
      />
      {renderTimeoutMessage()}
      {renderInventoryFullMessage()}
    </div>
  );
};

export default CraftActionPanel;
