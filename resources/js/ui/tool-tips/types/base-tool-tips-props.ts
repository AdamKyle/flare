import { ReactNode } from 'react';

import { TooltipAlign, TooltipPlacement } from './tooltip-placement';

export default interface BaseToolTipProps {
  tooltipId: string;
  label: string;
  align?: TooltipAlign;
  size?: 'sm' | 'md';
  is_open?: boolean;
  on_open?: () => void;
  on_close?: () => void;
  content: string | ReactNode;
  trigger?: ReactNode;
  trigger_aria_label?: string;
  placement?: TooltipPlacement;
}
