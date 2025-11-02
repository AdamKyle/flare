import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { baseProgressBarStyle } from 'ui/loading-bar/styles/base-progress-bar-style';
import { progressHeightVariantStyle } from 'ui/loading-bar/styles/progress-height-variant-style';
import ProgressBarProps from 'ui/loading-bar/types/progress-bar-props';

const ProgressBar = (props: ProgressBarProps): ReactNode => (
  <div className="my-4 flex w-full flex-col items-center space-y-2">
    <div className="flex w-full justify-between text-xs">
      <span className="text-gray-600 dark:text-gray-300">{props.label}</span>
      <span className="text-gray-600 dark:text-gray-300">
        {props.progress}%
      </span>
    </div>
    <div
      className={clsx(
        baseProgressBarStyle(),
        progressHeightVariantStyle(props.variant)
      )}
    >
      <div
        style={{ width: `${props.progress}%` }}
        className="bg-danube-500 dark:bg-danube-300 h-full transition-all"
      ></div>
    </div>
  </div>
);

export default ProgressBar;
