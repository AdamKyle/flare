import { ProgressBarVariant } from '../enums/progress-bar-variant';

export default interface ProgressBarProps {
  value: number;
  max: number;
  label: string;
  variant: ProgressBarVariant;
  value_label?: string;
  aria_label?: string;
  aria_labelledby?: string;
  additional_css?: string;
}
