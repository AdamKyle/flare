import React, { ReactNode } from 'react';

import FactionLoyaltyWarningsProps from './types/faction-loyalty-warnings-props';
import FactionLoyaltyWarningNoticeDefinition from '../api/definitions/faction-loyalty-warning-notice-definition';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const FactionLoyaltyWarnings = ({
  warning_notices: warningNotices,
  dismissing,
  on_dismiss: onDismiss,
}: FactionLoyaltyWarningsProps): ReactNode => {
  const renderWarning = (warning: FactionLoyaltyWarningNoticeDefinition) => (
    <li key={warning.id} className="space-y-2">
      <Alert variant={AlertVariant.WARNING}>{warning.message}</Alert>
      <Button
        label="Dismiss Warning"
        aria_label={`Dismiss warning: ${warning.message}`}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full sm:w-auto"
        disabled={dismissing}
        on_click={() => onDismiss(warning.id)}
      />
    </li>
  );

  if (warningNotices.length === 0) {
    return null;
  }

  return (
    <section aria-label="Faction Loyalty warnings">
      <ul className="space-y-3">{warningNotices.map(renderWarning)}</ul>
    </section>
  );
};

export default FactionLoyaltyWarnings;
