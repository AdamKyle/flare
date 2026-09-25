import MarketListingSummaryDefinition from './market-listing-summary-definition';
import ItemDetails from '../../../../api-definitions/items/item-details';

export default interface MarketListingDefinition extends MarketListingSummaryDefinition {
  item: ItemDetails;
}
