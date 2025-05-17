import { ReactNode } from 'react';

export default interface SidePeekProps {
  is_open: boolean;
  title: string;
  on_close?: () => void;
  allow_clicking_outside?: boolean;
  children?: ReactNode;
  has_footer?: boolean;
  footer_primary_label?: string;
  footer_secondary_label?: string;
  footer_primary_action?: () => void;
  footer_secondary_action?: () => void;
}
