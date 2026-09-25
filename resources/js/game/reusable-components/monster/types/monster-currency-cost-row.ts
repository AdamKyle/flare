import { CurrencyType } from '../../currency/enums/currency-type';

export default interface MonsterCurrencyCostRow {
  label: string;
  currency: CurrencyType;
  value: number;
}
