import React, { ReactNode } from 'react';

import CraftActionPanelProps from './types/craft-action-panel-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';

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
      <p className="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
        <span>Cost:</span>
        <CurrencyDisplay
          currency={CurrencyType.GOLD}
          amount={selectedItem.cost}
          display_mode={CurrencyDisplayMode.EXACT}
        />
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
