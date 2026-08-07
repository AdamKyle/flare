import React, { ReactNode } from 'react';

import CraftActionPanelProps from './types/craft-action-panel-props';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';

import { formatNumberWithCommas } from 'game-utils/format-number';

const CraftActionPanel = ({
  selectedItem,
  inventoryIsFull,
  isCraftSuccessful,
  onViewCraftedItem,
}: CraftActionPanelProps): ReactNode => {
  if (!selectedItem) {
    return null;
  }

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
    <CraftingActionPreview
      title="Item preview"
      status={isCraftSuccessful ? 'success' : 'default'}
    >
      <p className="text-xs text-gray-500 dark:text-gray-400">
        Cost: {formatNumberWithCommas(selectedItem.cost)} gold
      </p>
      <p className="text-xs text-gray-500 dark:text-gray-400">
        Skill Level Required: {selectedItem.skill_level_required} &bull; Trivial
        at: {selectedItem.skill_level_trivial}
      </p>
      <CraftingItemPreview
        item={selectedItem.preview}
        on_name_click={onViewCraftedItem}
      />
      {renderInventoryFullMessage()}
    </CraftingActionPreview>
  );
};

export default CraftActionPanel;
