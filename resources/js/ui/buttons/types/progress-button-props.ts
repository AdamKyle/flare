import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export default interface ProgressButtonProps {
  progress: number;
  on_click: () => void;
  label: string;
  variant: ButtonVariant;
  additional_css?: string;
}
