import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export default interface SidePeekOptionDefinition {
  id: string;
  label: string;
  variant: ButtonVariant;
  on_click: () => void;
  aria_label?: string;
  disabled?: boolean;
  loading?: boolean;
  loading_label?: string;
}
