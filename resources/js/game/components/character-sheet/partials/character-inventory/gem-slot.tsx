import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { backpackBaseItemStyles } from './styles/backpack-item-styles';
import {
  gemSlotBorderStyles,
  gemSlotButtonBackgroundColor,
  gemSlotFocusRingStyles,
  gemSlotTextColor,
} from './styles/gem-slot-styles';
import GemSlotProps from './types/gem-slot-props';

const GemSlot = ({ gem_slot }: GemSlotProps): ReactNode => {
  const itemColor = gemSlotTextColor(gem_slot);

  const renderGemSlotDetails = (): ReactNode => {
    return (
      <>
        <span>
          <strong>Tier</strong>: {gem_slot.tier}
        </span>{' '}
        |{' '}
        <span>
          <strong>Weak Against</strong>: {gem_slot.weak_against}
        </span>{' '}
        |{' '}
        <span>
          <strong>Strong Against</strong>: {gem_slot.strong_against}
        </span>{' '}
        |{' '}
        <span>
          <strong>Atoned To</strong>: {gem_slot.element_atoned_to}
        </span>{' '}
        |{' '}
        <span>
          <strong>Atoned To %</strong>:{' '}
          {(gem_slot.element_atoned_to_amount * 100).toFixed(2)}%
        </span>
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
    >
      <i className="ra ra-bone-knife text-2xl text-gray-800 dark:text-gray-600"></i>
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
