import React from 'react';

import { AlertVariant } from 'ui/alerts/enums/alert-variant';

export default interface AlertProps {
  variant: AlertVariant;
  children: React.ReactNode;
  closable?: boolean;
  on_close?: () => void;
  force_close?: boolean;
}
