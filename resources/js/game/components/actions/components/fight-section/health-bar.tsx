import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { fetchHealthBarColorForType } from './helpers/fetch-health-bar-color-for-type';
import { healthBarPercentage } from './helpers/fetch-health-bar-percentage';
import HealthBarProps from './types/health-bar-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

const HealthBar = (props: HealthBarProps): ReactNode => {
  return (
    <div className="space-y-2 mb-4">
      <div className="flex justify-between text-sm font-medium text-gray-800 dark:text-gray-200">
        <span id={props.name + '-health-bar'} className="sr-only">
          {props.name}
        </span>
        <span>{props.name}</span>
        <span aria-labelledby={props.name + '-health-bar'} aria-live="polite">
          {formatNumberWithCommas(props.current_health)}/
          {formatNumberWithCommas(props.max_health)}
        </span>
      </div>
      <div className="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-2">
        <div
          className={clsx(
            fetchHealthBarColorForType(props.health_bar_type),
            'rounded-full h-full'
          )}
          style={{
            width:
              healthBarPercentage(props.current_health, props.max_health) + '%',
          }}
          role="progressbar"
          aria-valuenow={props.current_health}
          aria-valuemin={0}
          aria-valuemax={props.max_health}
        ></div>
      </div>
    </div>
  );
};

export default HealthBar;
