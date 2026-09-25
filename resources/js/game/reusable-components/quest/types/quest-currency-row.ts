import { CurrencyType } from '../../currency/enums/currency-type';

export default interface QuestCurrencyRow {
  label: string;
  currency: CurrencyType;
  value: number;
}
