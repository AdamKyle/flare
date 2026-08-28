import React from 'react';

import { FactionLoyaltyLog } from '../api/definitions/faction-loyalty-monitoring-definition';

export interface MonitorCardProps {
  children: React.ReactNode;
  onClick?: () => void;
  ariaLabel?: string;
}

export interface LogDetailsProps {
  log: FactionLoyaltyLog | null | undefined;
}
