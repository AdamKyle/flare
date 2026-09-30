import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CharacterGemCompactSummary from '../../../../reusable-components/character-gem/character-gem-compact-summary';
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

  return (
    <div
      className={clsx(
        backpackBaseItemStyles(),
        'relative',
        gemSlotBorderStyles(gem_slot),
        gemSlotButtonBackgroundColor(gem_slot)
      )}
    >
      <button
        type="button"
        className={clsx(
          'absolute inset-0 rounded-lg focus:outline-none focus-visible:ring-2',
          gemSlotFocusRingStyles(gem_slot)
        )}
        onClick={handleViewGem}
        aria-label={`View ${gem_slot.name} details`}
      />
      <i
        className="ra ra-bone-knife pointer-events-none relative text-2xl text-gray-800 dark:text-gray-600"
        aria-hidden="true"
      ></i>
      <div className="pointer-events-none relative min-w-0 flex-1 text-left">
        <div className={clsx('text-lg font-semibold', itemColor)}>
          {gem_slot.name}
        </div>
        <div className={clsx('mt-1', itemColor)}>
          <CharacterGemCompactSummary modifiers={gem_slot.modifiers} />
        </div>
      </div>
    </div>
  );
};

export default GemSlot;
