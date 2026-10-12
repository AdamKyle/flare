import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';

export default interface XpBarProps {
  current_xp: number;
  max_xp: number;
  label?: string;
  value_label?: string;
  variant?: ProgressBarVariant;
  on_label_click?: () => void;
  aria_label?: string;
}
