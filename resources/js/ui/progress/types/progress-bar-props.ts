import { ProgressBarSize } from '../enums/progress-bar-size';
import { ProgressBarVariant } from '../enums/progress-bar-variant';

export default interface ProgressBarProps {
  value: number;
  max: number;
  label: string;
  variant: ProgressBarVariant;
  size?: ProgressBarSize;
  value_label?: string;
  aria_label?: string;
  aria_labelledby?: string;
  additional_css?: string;
}
