import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { ProgressBarSize } from './enums/progress-bar-size';
import {
  baseFillStyles,
  baseTrackStyles,
} from './styles/progress-bar/base-styles';
import { cardMutedTextVariantStyles } from './styles/progress-bar/card-variant-styles';
import { fillVariantStyles } from './styles/progress-bar/fill-variant-styles';
import { trackVariantStyles } from './styles/progress-bar/track-variant-styles';
import ProgressBarProps from './types/progress-bar-props';

const ProgressBar = (props: ProgressBarProps): ReactNode => {
  const size = props.size ?? ProgressBarSize.DEFAULT;
  const clampedMax = props.max > 0 ? props.max : 1;
  const clampedValue = Math.min(Math.max(props.value, 0), clampedMax);
  const percentage = Math.min(
    Math.max((clampedValue / clampedMax) * 100, 0),
    100
  );

  const labelId =
    props.aria_labelledby ??
    `progress-label-${props.label.replace(/\s+/g, '-').toLowerCase()}`;

  return (
    <div className={clsx('w-full', props.additional_css)}>
      <div
        className={clsx(
          'mb-1 flex justify-between text-sm font-medium',
          cardMutedTextVariantStyles(props.variant)
        )}
      >
        <span id={labelId}>{props.label}</span>
        {props.value_label !== undefined && <span>{props.value_label}</span>}
      </div>
      <div
        role="progressbar"
        aria-valuemin={0}
        aria-valuemax={clampedMax}
        aria-valuenow={clampedValue}
        aria-label={props.aria_label}
        aria-labelledby={props.aria_label ? undefined : labelId}
        className={clsx(
          baseTrackStyles(size),
          trackVariantStyles(props.variant)
        )}
      >
        <div
          className={clsx(baseFillStyles(), fillVariantStyles(props.variant))}
          style={{ width: `${percentage}%` }}
        />
      </div>
    </div>
  );
};

export default ProgressBar;
