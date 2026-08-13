import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React from 'react';

import ConjureConfirmationProps from './types/conjure-confirmation-props';
import { ConjureType } from '../api/enums/conjure-type';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const ConjureConfirmation = ({
  type,
  celestial_name,
  gold_cost,
  gold_dust_cost,
  is_submitting,
  api_error,
  on_confirm,
  on_cancel,
}: ConjureConfirmationProps) => {
  const isPrivate = type === ConjureType.PRIVATE;

  const renderApiError = () => {
    if (!api_error) {
      return null;
    }

    return <ApiErrorAlert apiError={api_error} />;
  };

  const renderActions = () => {
    if (is_submitting) {
      return (
        <div role="status" aria-live="polite">
          <InfiniteLoader />
          <span className="sr-only">Conjuring celestial.</span>
        </div>
      );
    }

    return (
      <div className="flex flex-col gap-2 sm:flex-row">
        <Button
          on_click={on_cancel}
          label="Cancel"
          variant={ButtonVariant.DANGER}
          additional_css={'flex-1'}
        />
        <Button
          on_click={on_confirm}
          label={isPrivate ? 'Conjure Privately' : 'Conjure Publicly'}
          variant={ButtonVariant.PRIMARY}
          additional_css={'flex-1'}
        />
      </div>
    );
  };

  return (
    <StackedCard on_close={on_cancel} aria_label="Are you sure?">
      <h2 className="mb-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Are you sure?
      </h2>

      <p className="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
        {isPrivate ? 'Private Conjuration' : 'Public Conjuration'}
      </p>

      <p className="text-sm text-gray-700 dark:text-gray-300">
        {isPrivate
          ? "You will pay the costs shown below. The celestial's coordinates will be sent to your Server Messages. Only you can see and fight this celestial."
          : 'You will pay the costs shown below. This celestial and its coordinates will be announced in General Chat. Other players can travel to it and fight it for the rewards.'}
      </p>

      <Separator />

      <Dl>
        <Dt>Celestial:</Dt>
        <Dd>{celestial_name}</Dd>

        <Dt>Gold Cost:</Dt>
        <Dd>{formatNumberWithCommas(gold_cost)}</Dd>

        <Dt>Gold Dust Cost:</Dt>
        <Dd>{formatNumberWithCommas(gold_dust_cost)}</Dd>
      </Dl>

      <Separator />

      {renderApiError()}

      {renderActions()}
    </StackedCard>
  );
};

export default ConjureConfirmation;
