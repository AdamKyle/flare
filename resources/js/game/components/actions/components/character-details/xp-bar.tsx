import React, { ReactNode } from 'react';

import { xpbarPercentage } from './helpers/xp-bar-percentage';
import XpBarProps from './types/xp-bar-props';

const XpBar = (props: XpBarProps): ReactNode => {
  return (
    <div className="space-y-2 mb-6">
      <div className="flex justify-between text-sm font-medium text-gray-800 dark:text-gray-200">
        <span id="xp-label" className="sr-only">
          Xp
        </span>
        <span>Xp</span>
        <span aria-labelledby="xp-label" aria-live="polite">
          {props.current_xp}/{props.max_xp}
        </span>
      </div>
      <div className="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-2">
        <div
          className={'bg-orange-600 dark:bg-orange-500 rounded-full h-full'}
          style={{
            width: xpbarPercentage(props.current_xp, props.max_xp) + '%',
          }}
          role="progressbar"
          aria-valuenow={props.current_xp}
          aria-valuemin={0}
          aria-valuemax={props.max_xp}
        ></div>
      </div>
    </div>
  );
};

export default XpBar;
