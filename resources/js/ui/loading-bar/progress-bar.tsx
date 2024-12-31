import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { baseProgressBarStyle } from 'ui/loading-bar/styles/base-progress-bar-style';
import { progressHeightVariantStyle } from 'ui/loading-bar/styles/progress-height-variant-style';
import ProgressBarProps from 'ui/loading-bar/types/progress-bar-props';

const ProgressBar = (props: ProgressBarProps): ReactNode => (
  <div className="w-full flex flex-col items-center space-y-2 my-4">
    <div className="w-full text-xs flex justify-between">
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
        className="h-full bg-danube-500 dark:bg-danube-300 transition-all"
      ></div>
    </div>
  </div>
);

export default ProgressBar;
