import React from 'react';

import AdjustmentChangeDisplayProps from '../../types/partials/item-comparison/adjustment-change-display-props';
import {
  formatSignedAuto,
  formatSignedPercent,
  getScreenReaderExplanation,
} from '../../utils/item-comparison';
import SignedAdjustment from '../signed-adjustment';

const resolveDisplayText = (
  value: number,
  renderAsPercent: boolean | undefined
): string => {
  if (value === 0) {
    return '0';
  }

  if (renderAsPercent) {
    return formatSignedPercent(value);
  }

  return formatSignedAuto(value);
};

const AdjustmentChangeDisplay = ({
  value,
  label,
  renderAsPercent,
}: AdjustmentChangeDisplayProps) => (
  <SignedAdjustment
    value={value}
    display_text={resolveDisplayText(value, renderAsPercent)}
    screen_reader_text={getScreenReaderExplanation(value, label)}
  />
);

export default AdjustmentChangeDisplay;
