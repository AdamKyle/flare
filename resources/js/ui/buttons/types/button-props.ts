import { ButtonVariant } from '../enums/button-variant-enum';

export default interface ButtonProps<T extends unknown[] = []> {
  on_click: (...args: T) => void;
  label: string;
  variant: ButtonVariant;
  disabled?: boolean;
  additional_css?: string;
  aria_lebel?: string;
}
