import { ReactNode } from 'react';

import MarketListingDefinition from '../../api/definitions/market-listing-definition';

export default interface MarketListingCardProps {
  listing: MarketListingDefinition;
  actions: ReactNode;
  status?: string;
}
