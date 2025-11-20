import { ReactNode } from 'react';

import { ButtonVariant } from '../enums/button-variant-enum';

export default interface IconButtonProps {
  on_click: () => void;
  variant: ButtonVariant;
  icon?: ReactNode;
  label?: string;
  disabled?: boolean;
  additional_css?: string;
  aria_label?: string;
  center_content?: boolean;
}
