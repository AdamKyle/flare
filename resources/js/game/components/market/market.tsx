import React, { ReactNode } from 'react';

import MarketListings from './components/market-listings';
import MyMarketListings from './components/my-market-listings';
import { MarketProvider } from './context/market-context';
import MarketProps from './types/market-props';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import PillTabs from 'ui/tabs/pill-tabs';

const MARKET_TABS = [
  { label: 'Listings', component: MarketListings },
  { label: 'My Listings', component: MyMarketListings },
] as const;

const Market = ({ close_market }: MarketProps): ReactNode => {
  return (
    <MarketProvider>
      <ContainerWithTitle manageSectionVisibility={close_market} title="Market">
        <Card>
          <p className="mb-4 text-sm text-gray-700 dark:text-gray-300">
            Buy items other players have listed, or manage your own listings.
            Buyers pay a 5% tax on top of the listed price and sellers receive
            the listed price minus a 5% fee.
          </p>
          <PillTabs
            tabs={MARKET_TABS}
            ariaLabel="Market sections"
            initialIndex={0}
          />
        </Card>
      </ContainerWithTitle>
    </MarketProvider>
  );
};

export default Market;
