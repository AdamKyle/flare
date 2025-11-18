import { ReactNode } from 'react';

import { ButtonVariant } from '../enums/button-variant-enum';

export default interface DropdownButtonProps {
  label: string;
  variant: ButtonVariant;
  children: ReactNode;
  on_click?: () => void;
  disabled?: boolean;
  additional_css?: string;
  aria_label?: string;
}
