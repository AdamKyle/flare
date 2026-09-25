import MarketListingDefinition from './market-listing-definition';

export default interface MarketListingResponseDefinition {
  message?: string;
  listing: MarketListingDefinition;
}
