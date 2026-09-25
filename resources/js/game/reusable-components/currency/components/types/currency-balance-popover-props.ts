import CurrencyPresentationDefinition from '../../types/currency-presentation-definition';

export default interface CurrencyBalancePopoverProps {
  presentation: CurrencyPresentationDefinition;
  amount: number;
  limit: number | null;
}
