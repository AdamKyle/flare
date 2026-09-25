import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosRequestConfig } from 'axios';
import { useCallback } from 'react';

import UseManageMarketListingDefinition from './definitions/use-manage-market-listing-definition';
import { useMarketMutation } from './use-market-mutation';
import MarketDelistResponseDefinition from '../definitions/market-delist-response-definition';
import MarketListingPriceRequestDefinition from '../definitions/market-listing-price-request-definition';
import MarketListingResponseDefinition from '../definitions/market-listing-response-definition';
import MarketMessageResponseDefinition from '../definitions/market-message-response-definition';
import { MarketApis } from '../enums/market-apis';

type ManageMarketListingResponse =
  | MarketListingResponseDefinition
  | MarketMessageResponseDefinition
  | MarketDelistResponseDefinition;

export const useManageMarketListing = (
  characterId: number
): UseManageMarketListingDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const beginEditMutation = useMarketMutation<MarketListingResponseDefinition>(
    'Unable to start editing this listing.'
  );
  const updateMutation = useMarketMutation<MarketListingResponseDefinition>(
    'Unable to update this listing.'
  );
  const cancelMutation = useMarketMutation<MarketMessageResponseDefinition>(
    'Unable to stop editing this listing.'
  );
  const delistMutation = useMarketMutation<MarketDelistResponseDefinition>(
    'Unable to delist this listing.'
  );

  const { run: runBeginEdit } = beginEditMutation;
  const { run: runUpdate } = updateMutation;
  const { run: runCancel } = cancelMutation;
  const { run: runDelist } = delistMutation;

  const buildUrl = useCallback(
    (url: MarketApis, listingId: number) =>
      getUrl(url, { marketBoard: listingId, character: characterId }),
    [getUrl, characterId]
  );

  const beginEdit = useCallback(
    (listingId: number) =>
      runBeginEdit((signal) =>
        apiHandler.post<
          MarketListingResponseDefinition,
          AxiosRequestConfig<ManageMarketListingResponse>,
          Record<string, never>
        >(buildUrl(MarketApis.BEGIN_LISTING_EDIT, listingId), {}, { signal })
      ),
    [apiHandler, buildUrl, runBeginEdit]
  );

  const updatePrice = useCallback(
    (listingId: number, listedPrice: number) =>
      runUpdate((signal) =>
        apiHandler.patch<
          MarketListingResponseDefinition,
          AxiosRequestConfig<ManageMarketListingResponse>,
          MarketListingPriceRequestDefinition
        >(
          buildUrl(MarketApis.OWNED_LISTING, listingId),
          { listed_price: listedPrice },
          { signal }
        )
      ),
    [apiHandler, buildUrl, runUpdate]
  );

  const cancelEdit = useCallback(
    (listingId: number) =>
      runCancel((signal) =>
        apiHandler.post<
          MarketMessageResponseDefinition,
          AxiosRequestConfig<ManageMarketListingResponse>,
          Record<string, never>
        >(buildUrl(MarketApis.CANCEL_LISTING_EDIT, listingId), {}, { signal })
      ),
    [apiHandler, buildUrl, runCancel]
  );

  const delist = useCallback(
    (listingId: number) =>
      runDelist((signal) =>
        apiHandler.delete<
          MarketDelistResponseDefinition,
          AxiosRequestConfig<ManageMarketListingResponse>
        >(buildUrl(MarketApis.OWNED_LISTING, listingId), { signal })
      ),
    [apiHandler, buildUrl, runDelist]
  );

  return {
    loading:
      beginEditMutation.loading ||
      updateMutation.loading ||
      cancelMutation.loading ||
      delistMutation.loading,
    error:
      beginEditMutation.error ??
      updateMutation.error ??
      cancelMutation.error ??
      delistMutation.error,
    begin_edit: beginEdit,
    update_price: updatePrice,
    cancel_edit: cancelEdit,
    delist,
  };
};
