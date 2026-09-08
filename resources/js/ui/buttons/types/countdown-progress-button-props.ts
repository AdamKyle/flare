import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export default interface CountdownProgressButtonProps {
  started_at: string;
  complete_at: string;
  label_prefix: string;
  variant: ButtonVariant;
  on_click: () => void;
  additional_css?: string;
  disabled?: boolean;
  on_complete?: () => void;
  detailed_time?: boolean;
}
