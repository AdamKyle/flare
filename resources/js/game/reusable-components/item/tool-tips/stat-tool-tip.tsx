import React, { useId } from 'react';

import StatToolTipProps from './types/stat-tool-tip-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import BaseToolTip from 'ui/tool-tips/base-tool-tip';

const StatToolTip = (props: StatToolTipProps) => {
  const {
    label,
    value,
    renderAsPercent,
    align = 'right',
    size = 'sm',
    is_open,
    on_open,
    on_close,
    custom_message,
    message,
  } = props;

  const localId = useId();
  const tooltipId = `stat-info-${label.replace(/\s+/g, '-').toLowerCase()}-${localId}`;

  const getDirection = (
    signedValue: number
  ): 'increase' | 'decrease' | 'no-change' => {
    if (signedValue > 0) {
      return 'increase';
    }

    if (signedValue < 0) {
      return 'decrease';
    }

    return 'no-change';
  };

  const getAmountText = (signedValue: number): string => {
    const absoluteValue = Math.abs(signedValue);

    if (renderAsPercent || !Number.isInteger(signedValue)) {
      return `${(absoluteValue * 100).toFixed(2)}%`;
    }

    return formatNumberWithCommas(absoluteValue);
  };

  const getMessage = (): string | React.ReactNode => {
    if (custom_message) {
      return typeof message !== 'undefined' ? message : label;
    }

    const direction = getDirection(value);

    if (direction === 'no-change') {
      return `${label} will not change.`;
    }

    return `This will ${direction} your ${label} by ${getAmountText(value)}.`;
  };

  return (
    <BaseToolTip
      tooltipId={tooltipId}
      label={label}
      align={align}
      size={size}
      is_open={is_open}
      on_open={on_open}
      on_close={on_close}
      content={getMessage()}
      placementDeps={[label, value]}
    />
  );
};

export default StatToolTip;
