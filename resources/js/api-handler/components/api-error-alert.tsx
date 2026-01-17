import ApiErrorAlertProps from 'api-handler/components/types/api-error-alert-props';
import React from 'react';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const ApiErrorAlert = ({
  apiError,
  on_close,
  closable,
}: ApiErrorAlertProps) => {
  return (
    <Alert
      variant={AlertVariant.DANGER}
      closable={closable}
      on_close={on_close}
    >
      {apiError}
    </Alert>
  );
};

export default ApiErrorAlert;
