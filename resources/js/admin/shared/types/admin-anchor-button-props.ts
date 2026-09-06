import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export default interface AdminAnchorButtonProps {
  href: string;
  label: string;
  variant: ButtonVariant;
  aria_label?: string;
}
