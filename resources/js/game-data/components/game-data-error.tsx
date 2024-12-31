import React, { ReactNode } from 'react';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

export const GameDataError = (): ReactNode => {
  return (
    <Alert variant={AlertVariant.DANGER}>
      <p>
        Something is wrong child. The character data is missing. Head to discord
        and report this. You can access this via the right profile icon, click
        Discord and post this in #bugs.
      </p>
    </Alert>
  );
};
