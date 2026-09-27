import { CurrencyCacheTypeValue } from '../../../../../../reusable-components/usable-item/enums/currency-cache-type-labels';

export default interface CurrencyCacheUsableItemActionProps {
  currency_cache_type: CurrencyCacheTypeValue;
  cache_amount: number;
  is_using: boolean;
  on_use: () => void;
}
