import React, { createContext, useCallback, useMemo, useState } from 'react';

import MarketContextDefinition from './definitions/market-context-definition';
import MarketProviderProps from './types/market-provider-props';
import MarketListingSummaryDefinition from '../api/definitions/market-listing-summary-definition';
import { useMarketUpdatesWebsocket } from '../websockets/hooks/use-market-updates-websocket';

import { useGameData } from 'game-data/hooks/use-game-data';

const MarketContext = createContext<MarketContextDefinition | undefined>(
  undefined
);

const MarketProvider = ({ children }: MarketProviderProps) => {
  const { gameData, updateCharacter } = useGameData();

  const [realtimeVersion, setRealtimeVersion] = useState(0);
  const [availableListingIds, setAvailableListingIds] =
    useState<Set<number> | null>(null);

  const characterId = gameData?.character?.id ?? 0;

  const handleMarketUpdate = useCallback(
    (listings: MarketListingSummaryDefinition[]) => {
      setAvailableListingIds(new Set(listings.map((listing) => listing.id)));
      setRealtimeVersion((previousVersion) => previousVersion + 1);
    },
    []
  );

  useMarketUpdatesWebsocket({
    enabled: characterId > 0,
    on_market_update: handleMarketUpdate,
  });

  const isListingAvailable = useCallback(
    (listingId: number) =>
      availableListingIds === null || availableListingIds.has(listingId),
    [availableListingIds]
  );

  const contextValue = useMemo<MarketContextDefinition>(
    () => ({
      character_id: characterId,
      realtime_version: realtimeVersion,
      is_listing_available: isListingAvailable,
      update_character: updateCharacter,
    }),
    [characterId, realtimeVersion, isListingAvailable, updateCharacter]
  );

  return (
    <MarketContext.Provider value={contextValue}>
      {children}
    </MarketContext.Provider>
  );
};

export { MarketContext, MarketProvider };
