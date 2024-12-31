import { ProgressBarHeightVariant } from 'ui/loading-bar/enums/progress-bar-height-variant';

export default interface ProgressBarProps {
  progress: number;
  label: string;
  variant: ProgressBarHeightVariant;
}
