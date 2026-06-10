import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { baseTrackStyles } from './styles/progress-bar/base-styles';
import { fillVariantStyles } from './styles/progress-bar/fill-variant-styles';
import { trackVariantStyles } from './styles/progress-bar/track-variant-styles';
import IndeterminateProgressBarProps from './types/indeterminate-progress-bar-props';

const IndeterminateProgressBar = (
  props: IndeterminateProgressBarProps
): ReactNode => {
  return (
    <div
      role="status"
      aria-live="polite"
      className={clsx('w-full', props.additional_css)}
    >
      <div className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
        {props.label}
      </div>
      <div
        className={clsx(baseTrackStyles(), trackVariantStyles(props.variant))}
      >
        <div
          className={clsx(
            'h-full w-full animate-pulse rounded-full',
            fillVariantStyles(props.variant)
          )}
        />
      </div>
    </div>
  );
};

export default IndeterminateProgressBar;
