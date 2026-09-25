import React, { ReactNode } from 'react';

import SetStatusNoticesProps from './types/set-status-notices-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const SetStatusNotices = ({
  selected_set,
  restriction_message,
  show_restriction,
  is_violating_set_rules,
}: SetStatusNoticesProps): ReactNode => {
  const renderEquippedStatus = (): ReactNode => {
    if (!selected_set.equipped) {
      return null;
    }

    return (
      <p className="text-danube-700 dark:text-danube-300 text-sm font-semibold">
        You are looking at your current equipped set
      </p>
    );
  };

  const renderRestriction = (): ReactNode => {
    if (!show_restriction || restriction_message === null) {
      return null;
    }

    return <Alert variant={AlertVariant.WARNING}>{restriction_message}</Alert>;
  };

  const renderSetRuleViolation = (): ReactNode => {
    if (!is_violating_set_rules) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        Cannot equip set because it violates the set rules. You can still treat
        this set like a stash tab.{' '}
        <a
          href="/information/equipment-sets"
          target="_blank"
          rel="noopener noreferrer"
          className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
        >
          Learn about equipment sets (opens in a new tab)
        </a>
      </Alert>
    );
  };

  const renderEmptyDisabledReason = (): ReactNode => {
    if (
      selected_set.equipped ||
      selected_set.can_empty ||
      selected_set.empty_disabled_reason === null
    ) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        {selected_set.empty_disabled_reason}
      </Alert>
    );
  };

  return (
    <div className="flex flex-col gap-2">
      {renderEquippedStatus()}
      {renderRestriction()}
      {renderSetRuleViolation()}
      {renderEmptyDisabledReason()}
    </div>
  );
};

export default SetStatusNotices;
