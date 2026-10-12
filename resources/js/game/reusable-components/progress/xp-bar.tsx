import React, { ReactNode } from 'react';

import XpBarProps from './types/xp-bar-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const XpBar = ({
  current_xp: currentXp,
  max_xp: maxXp,
  label = 'Xp',
  value_label: valueLabel,
  variant = ProgressBarVariant.XP,
  on_label_click: onLabelClick,
  aria_label: ariaLabel,
}: XpBarProps): ReactNode => (
  <ProgressBar
    label={label}
    value={currentXp}
    max={maxXp}
    value_label={
      valueLabel ??
      `${formatNumberWithCommas(currentXp)}/${formatNumberWithCommas(maxXp)}`
    }
    size={ProgressBarSize.THIN}
    variant={variant}
    on_label_click={onLabelClick}
    aria_label={ariaLabel}
  />
);

export default XpBar;
