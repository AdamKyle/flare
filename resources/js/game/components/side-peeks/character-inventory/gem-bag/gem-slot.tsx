import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CharacterGemModifierList from '../../../../reusable-components/character-gem/character-gem-modifier-list';
import { backpackBaseItemStyles } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import {
  gemSlotBorderStyles,
  gemSlotButtonBackgroundColor,
  gemSlotFocusRingStyles,
  gemSlotTextColor,
} from '../../../character-sheet/partials/character-inventory/styles/gem-slot-styles';
import GemSlotProps from '../../../character-sheet/partials/character-inventory/types/gem-slot-props';

const GemSlot = ({ gem_slot, on_view_gem }: GemSlotProps): ReactNode => {
  const itemColor = gemSlotTextColor(gem_slot);

  const handleViewGem = () => {
    on_view_gem(gem_slot.slot_id);
  };

  const renderGemSlotDetails = (): ReactNode => {
    return (
      <>
        <span>
          <strong>Tier</strong>: {gem_slot.tier}
        </span>
        <CharacterGemModifierList modifiers={gem_slot.modifiers} />
      </>
    );
  };

  return (
    <button
      className={clsx(
        backpackBaseItemStyles(),
        gemSlotFocusRingStyles(gem_slot),
        gemSlotBorderStyles(gem_slot),
        gemSlotButtonBackgroundColor(gem_slot)
      )}
      onClick={handleViewGem}
      type="button"
    >
      <i
        className="ra ra-bone-knife text-2xl text-gray-800 dark:text-gray-600"
        aria-hidden="true"
      ></i>
      <div className="text-left">
        <div className={clsx('text-lg font-semibold', itemColor)}>
          {gem_slot.name}
        </div>
        <div className={clsx('text-sm', itemColor)}>
          {renderGemSlotDetails()}
        </div>
      </div>
    </button>
  );
};

export default GemSlot;
