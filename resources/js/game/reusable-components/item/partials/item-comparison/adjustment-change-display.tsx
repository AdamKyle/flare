import clsx from 'clsx';
import React from 'react';

import AdjustmentChangeDisplayProps from '../../types/partials/item-comparison/adjustment-change-display-props';
import {
  formatSignedAuto,
  formatSignedPercent,
  getScreenReaderExplanation,
} from '../../utils/item-comparison';

const AdjustmentChangeDisplay = ({
  value,
  label,
  renderAsPercent,
}: AdjustmentChangeDisplayProps) => {
  const getChangeIcon = (signedValue: number): React.ReactNode => {
    if (signedValue > 0) {
      return (
        <i className="fas fa-chevron-up text-emerald-600" aria-hidden="true" />
      );
    }

    if (signedValue < 0) {
      return (
        <i className="fas fa-chevron-down text-rose-600" aria-hidden="true" />
      );
    }

    return <i className="fas fa-minus text-gray-500" aria-hidden="true" />;
  };

  const getValueClassName = (signedValue: number): string => {
    if (signedValue > 0) {
      return 'text-emerald-600';
    }

    if (signedValue < 0) {
      return 'text-rose-600';
    }

    return 'text-gray-700 dark:text-gray-300';
  };

  const icon = getChangeIcon(value);
  const valueClassName = getValueClassName(value);
  const isZero = value === 0;

  let displayText: string;

  if (isZero) {
    displayText = '0';
  } else if (renderAsPercent) {
    displayText = formatSignedPercent(value);
  } else {
    displayText = formatSignedAuto(value);
  }

  return (
    <span className="flex items-center justify-end gap-1 tabular-nums">
      {icon}
      <span className={clsx(valueClassName)} aria-hidden="true">
        {displayText}
      </span>
      <span className="sr-only">
        {getScreenReaderExplanation(value, label)}
      </span>
    </span>
  );
};

export default AdjustmentChangeDisplay;
