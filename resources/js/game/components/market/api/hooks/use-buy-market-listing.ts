import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosRequestConfig } from 'axios';
import { useCallback } from 'react';

import UseBuyMarketListingDefinition from './definitions/use-buy-market-listing-definition';
import { useMarketMutation } from './use-market-mutation';
import MarketPurchaseResponseDefinition from '../definitions/market-purchase-response-definition';
import { MarketApis } from '../enums/market-apis';

export const useBuyMarketListing = (
  characterId: number
): UseBuyMarketListingDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { loading, error, run } =
    useMarketMutation<MarketPurchaseResponseDefinition>(
      'Unable to buy this listing.'
    );

  const buy = useCallback(
    (listingId: number) =>
      run((signal) =>
        apiHandler.post<
          MarketPurchaseResponseDefinition,
          AxiosRequestConfig<MarketPurchaseResponseDefinition>,
          Record<string, never>
        >(
          getUrl(MarketApis.BUY_LISTING, {
            marketBoard: listingId,
            character: characterId,
          }),
          {},
          { signal }
        )
      ),
    [apiHandler, getUrl, run, characterId]
  );

  return { loading, error, buy };
};
