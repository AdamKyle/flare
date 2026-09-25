import { CurrencyDisplayMode } from '../enums/currency-display-mode';
import { CurrencyType } from '../enums/currency-type';

export default interface CurrencyDisplayProps {
  currency: CurrencyType;
  amount: number;
  display_mode: CurrencyDisplayMode;
  label?: string;
  show_label?: boolean;
  additional_css?: string;
}
