import MarketListingSummaryDefinition from '../../../api/definitions/market-listing-summary-definition';

export default interface UseMarketUpdatesWebsocketParams {
  enabled: boolean;
  on_market_update: (listings: MarketListingSummaryDefinition[]) => void;
}
