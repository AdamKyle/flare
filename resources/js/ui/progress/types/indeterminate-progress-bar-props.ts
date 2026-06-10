import { ProgressBarVariant } from '../enums/progress-bar-variant';

export default interface IndeterminateProgressBarProps {
  label: string;
  variant: ProgressBarVariant;
  additional_css?: string;
}
