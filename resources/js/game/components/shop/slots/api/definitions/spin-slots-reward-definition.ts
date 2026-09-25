import { CurrencyType } from '../../../../../reusable-components/currency/enums/currency-type';

export default interface SpinSlotsRewardDefinition {
  currency: CurrencyType;
  amount: number;
  balance: number;
}
