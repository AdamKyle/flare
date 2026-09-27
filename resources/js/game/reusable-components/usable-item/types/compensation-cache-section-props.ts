import { CurrencyCacheTypeValue } from '../enums/currency-cache-type-labels';

export default interface CompensationCacheSectionProps {
  currency_cache_type: CurrencyCacheTypeValue;
  cache_amount: number;
  show_separator: boolean;
}
