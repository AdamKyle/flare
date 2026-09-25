import React, { ReactNode } from 'react';

import ExplorationBusyWarningProps from '../types/exploration-busy-warning-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const ExplorationBusyWarning = ({
  automation_name: automationName,
  blocked,
}: ExplorationBusyWarningProps): ReactNode => {
  return (
    <Alert variant={AlertVariant.WARNING}>
      You are currently busy with {automationName} automation.
      {blocked && ' Cancel the automation first to use this action.'}
    </Alert>
  );
};

export default ExplorationBusyWarning;
