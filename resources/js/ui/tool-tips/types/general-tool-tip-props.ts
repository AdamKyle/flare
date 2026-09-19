import React from 'react';

export default interface GeneralToolTipProps {
  label: string;
  message?: string | React.ReactNode;
  is_open?: boolean;
  on_open?: () => void;
  on_close?: () => void;
  align?: 'left' | 'right' | 'auto';
  size?: 'sm' | 'md';
  trigger?: React.ReactNode;
  trigger_aria_label?: string;
  placement?: 'auto' | 'above';
}
