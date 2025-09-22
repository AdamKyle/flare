import React from 'react';

import InfoAlertsProps from './types/info-alerts-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const InfoAlerts = ({ messages }: InfoAlertsProps) => {
  const visible = (messages || []).filter(Boolean) as string[];

  if (visible.length === 0) {
    return null;
  }

  return (
    <div className="w-full space-y-2">
      {visible.map((message, index) => (
        <Alert key={`info-${index}`} variant={AlertVariant.INFO}>
          {message}
        </Alert>
      ))}
    </div>
  );
};

export default InfoAlerts;
