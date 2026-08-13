import React from 'react';

import ConjureCostSectionProps from './types/conjure-cost-section-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const ConjureCostSection = ({
  gold_cost,
  gold_dust_cost,
  can_afford,
  on_request_private,
  on_request_public,
}: ConjureCostSectionProps) => {
  const renderCannotAfford = () => {
    if (can_afford) {
      return null;
    }

    return (
      <p className="text-rose-600 dark:text-rose-500" role="status">
        You cannot afford to conjure this celestial.
      </p>
    );
  };

  return (
    <div className="mt-4 space-y-3 rounded-lg border border-solid border-gray-200 bg-gray-100 p-4 text-sm dark:border-gray-800 dark:bg-gray-700">
      <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
        Conjuration Cost
      </h3>

      <Separator />

      <Dl>
        <Dt>Gold Cost:</Dt>
        <Dd>{formatNumberWithCommas(gold_cost)}</Dd>

        <Dt>Gold Dust Cost:</Dt>
        <Dd>{formatNumberWithCommas(gold_dust_cost)}</Dd>
      </Dl>

      {renderCannotAfford()}

      <div className="flex flex-col gap-2">
        <Button
          on_click={on_request_private}
          label="Conjure Privately"
          variant={ButtonVariant.PRIMARY}
          disabled={!can_afford}
          additional_css={'w-full'}
        />

        <div className="flex items-center justify-center gap-2">
          <span
            className="inline-block h-px w-10 bg-gray-300 dark:bg-gray-600"
            aria-hidden="true"
          />
          <span className="text-sm font-medium text-gray-500 dark:text-gray-400">
            Or
          </span>
          <span
            className="inline-block h-px w-10 bg-gray-300 dark:bg-gray-600"
            aria-hidden="true"
          />
        </div>

        <Button
          on_click={on_request_public}
          label="Conjure Publicly"
          variant={ButtonVariant.PRIMARY}
          disabled={!can_afford}
          additional_css={'w-full'}
        />
      </div>
    </div>
  );
};

export default ConjureCostSection;
