import { ButtonVariant } from '../enums/button-variant-enum';

interface ButtonBaseProps {
  label: string;
  variant: ButtonVariant;
  disabled?: boolean;
  additional_css?: string;
  aria_label?: string;
  aria_busy?: boolean;
}

type ButtonProps<T extends unknown[] = []> = ButtonBaseProps &
  (
    | { type: 'submit'; on_click?: (...args: T) => void }
    | { type?: 'button'; on_click: (...args: T) => void }
  );

export default ButtonProps;
