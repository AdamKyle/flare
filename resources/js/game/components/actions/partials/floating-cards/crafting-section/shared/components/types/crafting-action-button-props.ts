import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export default interface CraftingActionButtonProps {
  label: string;
  on_click: () => void;
  disabled?: boolean;
  variant?: ButtonVariant;
  aria_label?: string;
}
