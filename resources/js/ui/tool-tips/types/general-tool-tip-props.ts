import React from 'react';

import { TooltipAlign, TooltipPlacement } from './tooltip-placement';

export default interface GeneralToolTipProps {
  label: string;
  message?: string | React.ReactNode;
  is_open?: boolean;
  on_open?: () => void;
  on_close?: () => void;
  align?: TooltipAlign;
  size?: 'sm' | 'md';
  trigger?: React.ReactNode;
  trigger_aria_label?: string;
  placement?: TooltipPlacement;
}
