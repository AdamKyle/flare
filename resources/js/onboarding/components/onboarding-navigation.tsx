import React, { ReactNode } from 'react';

import OnboardingNavigationProps from '../types/onboarding-navigation-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const OnboardingNavigation = ({
  step,
  total,
  is_first: isFirst,
  is_last: isLast,
  submitting,
  submit_error: submitError,
  on_back: onBack,
  on_next: onNext,
  on_finish: onFinish,
}: OnboardingNavigationProps): ReactNode => {
  const renderSubmitError = () => {
    if (!submitError) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{submitError}</Alert>;
  };

  const renderBackButton = () => {
    if (isFirst) {
      return null;
    }

    return (
      <Button
        label="Back"
        variant={ButtonVariant.PRIMARY}
        on_click={onBack}
        disabled={submitting}
      />
    );
  };

  const renderForwardButton = () => {
    if (isLast) {
      return (
        <Button
          label="To the game!"
          variant={ButtonVariant.SUCCESS}
          on_click={onFinish}
          disabled={submitting}
          aria_busy={submitting}
        />
      );
    }

    return (
      <Button
        label="Next"
        variant={ButtonVariant.PRIMARY}
        on_click={onNext}
        disabled={submitting}
      />
    );
  };

  return (
    <div className="flex shrink-0 flex-col gap-2 border-t border-gray-300 px-4 py-3 md:px-8 dark:border-gray-700">
      {renderSubmitError()}

      <div className="flex items-center justify-between gap-2">
        <div>{renderBackButton()}</div>

        <p className="text-sm text-gray-600 dark:text-gray-400">
          Step {step} of {total}
        </p>

        {renderForwardButton()}
      </div>
    </div>
  );
};

export default OnboardingNavigation;
