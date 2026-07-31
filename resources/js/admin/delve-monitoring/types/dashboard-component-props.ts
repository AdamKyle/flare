import React from 'react';

import { DelveLogEntry } from '../api/definitions/delve-monitoring-definition';

export interface MonitorCardProps {
  children: React.ReactNode;
  onClick?: () => void;
  ariaLabel?: string;
}

export interface RunLogDetailsProps {
  logs: DelveLogEntry[];
}
