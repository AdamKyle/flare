import { ReactNode } from 'react';

import { ButtonVariant } from '../enums/button-variant-enum';

export default interface IconButtonProps<T extends unknown[] = []> {
  on_click: (...args: T) => void;
  icon: ReactNode;
  variant: ButtonVariant;
  label?: string;
  disabled?: boolean;
  additional_css?: string;
  aria_lebel?: string;
}
