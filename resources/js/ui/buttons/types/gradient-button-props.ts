import { ButtonGradientVarient } from '../enums/button-gradient-variant';

export default interface GradientButtonProps<T extends unknown[] = []> {
  on_click: (...args: T) => void;
  label: string;
  gradient: ButtonGradientVarient;
  disabled?: boolean;
  additional_css?: string;
  aria_lebel?: string;
}
