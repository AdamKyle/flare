import React, { ReactNode } from 'react';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

export const GameDataError = (): ReactNode => {
  return (
    <Alert variant={AlertVariant.DANGER}>
      <p>
        Whoops! We seem to have encountered an error that should not have happened. Please head to Discord and post a screen shot of this in #bugs. Please make sure to give as much details for your bug report as possible!
      </p>
    </Alert>
  );
};
