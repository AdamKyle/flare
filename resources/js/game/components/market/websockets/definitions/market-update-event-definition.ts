import MarketListingSummaryDefinition from '../../api/definitions/market-listing-summary-definition';

/**
 * `characterGold` belongs to the character whose action triggered the broadcast,
 * not to the viewer, so it must never be treated as the viewer's balance.
 */
export default interface MarketUpdateEventDefinition {
  marketListings: {
    data: MarketListingSummaryDefinition[];
  };
  characterGold: number;
}
