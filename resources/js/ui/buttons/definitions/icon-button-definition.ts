import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export default interface IconButtonDefinition {
  label: string;
  icon: string;
  variant: ButtonVariant;
  onClick: () => void;
  additionalCss?: string;
}
