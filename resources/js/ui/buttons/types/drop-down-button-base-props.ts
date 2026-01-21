import { ReactNode } from 'react';

import { ButtonVariant } from '../enums/button-variant-enum';

export default interface DropDownButtonBaseProps {
  label: string;
  variant: ButtonVariant;
  children: ReactNode;
  on_click?: () => void;
  close_on_click_outside?: boolean;
  disabled?: boolean;
  additional_css?: string;
  aria_label?: string;
}
