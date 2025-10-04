import React from 'react';

export default interface StatInfoToolTipProps {
  label: string;
  value: number;
  renderAsPercent?: boolean;
  is_open?: boolean;
  on_open?: () => void;
  on_close?: () => void;
  align?: 'left' | 'right' | 'auto';
  size?: 'sm' | 'md';
  custom_message?: boolean;
  message?: string | React.ReactNode;
}
