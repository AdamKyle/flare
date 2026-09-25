import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosRequestConfig } from 'axios';
import { useCallback } from 'react';

import UseBuyAndReplaceMarketListingDefinition from './definitions/use-buy-and-replace-market-listing-definition';
import { useMarketMutation } from './use-market-mutation';
import MarketBuyAndReplaceRequestDefinition from '../definitions/market-buy-and-replace-request-definition';
import MarketPurchaseResponseDefinition from '../definitions/market-purchase-response-definition';
import { MarketApis } from '../enums/market-apis';

export const useBuyAndReplaceMarketListing = (
  characterId: number
): UseBuyAndReplaceMarketListingDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { loading, error, run } =
    useMarketMutation<MarketPurchaseResponseDefinition>(
      'Unable to buy and equip this listing.'
    );

  const buyAndReplace = useCallback(
    (listingId: number, request: MarketBuyAndReplaceRequestDefinition) =>
      run((signal) =>
        apiHandler.post<
          MarketPurchaseResponseDefinition,
          AxiosRequestConfig<MarketPurchaseResponseDefinition>,
          MarketBuyAndReplaceRequestDefinition
        >(
          getUrl(MarketApis.BUY_AND_REPLACE_LISTING, {
            marketBoard: listingId,
            character: characterId,
          }),
          request,
          { signal }
        )
      ),
    [apiHandler, getUrl, run, characterId]
  );

  return { loading, error, buy_and_replace: buyAndReplace };
};
