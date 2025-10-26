import { ReactNode } from 'react';

export default interface BaseToolTipProps {
  tooltipId: string;
  label: string;
  align?: 'left' | 'right' | 'auto';
  size?: 'sm' | 'md';
  is_open?: boolean;
  on_open?: () => void;
  on_close?: () => void;
  content: string | ReactNode;
  placementDeps?: unknown[];
}
