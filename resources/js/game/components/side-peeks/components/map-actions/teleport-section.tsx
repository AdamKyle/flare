import clsx from 'clsx';
import React from 'react';

import TeleportSectionProps from './types/teleport-section-props';
import { formatNumberWithCommas } from '../../../../util/format-number';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const TeleportSection = ({
  cost_of_teleport,
  character_gold,
  on_teleport,
  can_afford_to_teleport,
  time_out_value,
}: TeleportSectionProps) => {
  if (cost_of_teleport <= 0) {
    return null;
  }

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
            can_afford_to_teleport
              ? 'text-emerald-600 dark:text-emerald-500'
              : 'text-rose-600 hover:text-rose-500'
          )}
        >
          {formatNumberWithCommas(cost_of_teleport)}
        </span>
      </div>
      <div className="flex justify-between">
        <span className="text-gray-800 dark:text-gray-200">
          Time Out value:
        </span>
        <span className="text-danube-600 hover:text-danube-500">
          {time_out_value} (Minutes)
        </span>
      </div>
      <Button
        on_click={on_teleport}
        label={'Teleport'}
        variant={ButtonVariant.PRIMARY}
        disabled={cost_of_teleport <= 0 || !can_afford_to_teleport}
        additional_css={'mt-2 w-full'}
      />
    </div>
  );
};

export default TeleportSection;
