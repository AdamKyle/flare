import clsx from 'clsx';
import React from 'react';

import SetSailSectionProps from './types/set-sail-section-props';
import { formatNumberWithCommas } from '../../../../../util/format-number';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const SetSailSection = ({
  character_gold,
  selected_port,
  on_set_sail,
  is_submitting,
}: SetSailSectionProps) => {
  const renderSetSailButtonOrLoading = () => {
    if (is_submitting) {
      return (
        <div role="status" aria-live="polite">
          <InfiniteLoader />
          <span className="sr-only">Setting sail.</span>
        </div>
      );
    }

    return (
      <Button
        on_click={on_set_sail}
        label={'Set Sail'}
        variant={ButtonVariant.PRIMARY}
        disabled={!selected_port.can_afford}
        additional_css={'mt-2 w-full'}
      />
    );
  };

  return (
    <div className="mt-4 space-y-2 rounded-lg border border-solid border-gray-200 bg-gray-100 p-4 text-sm dark:border-gray-800 dark:bg-gray-700">
      <div className="flex justify-between">
        <span className="font-medium text-gray-800 dark:text-gray-200">
          Your Gold:
        </span>
        <span className="font-mono text-gray-900 dark:text-gray-100">
          {formatNumberWithCommas(character_gold)}
        </span>
      </div>
      <div className="flex justify-between">
        <span className="font-medium text-gray-800 dark:text-gray-200">
          Cost:
        </span>
        <span
          className={clsx(
            'font-mono',
            selected_port.can_afford
              ? 'text-emerald-600 dark:text-emerald-500'
              : 'text-rose-600 dark:text-rose-500'
          )}
        >
          {formatNumberWithCommas(selected_port.cost)}
        </span>
      </div>
      <div className="flex justify-between">
        <span className="text-gray-800 dark:text-gray-200">Distance:</span>
        <span className="text-danube-600 dark:text-danube-300">
          {selected_port.distance} Miles
        </span>
      </div>
      <div className="flex justify-between">
        <span className="text-gray-800 dark:text-gray-200">
          Movement Timeout:
        </span>
        <span className="text-danube-600 dark:text-danube-300">
          {selected_port.time} Minutes
        </span>
      </div>
      {renderSetSailButtonOrLoading()}
    </div>
  );
};

export default SetSailSection;
