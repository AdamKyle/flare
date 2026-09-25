import { ItemComparison } from '../../../../api-definitions/items/item-comparison-details';

export default interface MarketComparisonResponseDefinition extends ItemComparison {
  market_board_id: number;
  listed_price: number;
  total_price: number;
}
