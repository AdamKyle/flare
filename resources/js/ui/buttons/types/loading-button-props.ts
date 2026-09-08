import { ButtonVariant } from '../enums/button-variant-enum';

export default interface LoadingButtonProps {
  label: string;
  loading_label: string;
  variant: ButtonVariant;
  on_click: () => void;
  is_loading: boolean;
  disabled?: boolean;
  additional_css?: string;
  aria_label?: string;
}
