import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseCompareMarketListingDefinition from './definitions/use-compare-market-listing-definition';
import MarketComparisonResponseDefinition from '../definitions/market-comparison-response-definition';
import { MarketApis } from '../enums/market-apis';

export const useCompareMarketListing = (
  listingId: number,
  characterId: number
): UseCompareMarketListingDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] =
    useState<UseCompareMarketListingDefinition['data']>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] =
    useState<UseCompareMarketListingDefinition['error']>(null);

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (listingId <= 0 || characterId <= 0) {
      setLoading(false);

      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchComparison = async () => {
      try {
        const result = await apiHandler.post<
          MarketComparisonResponseDefinition,
          AxiosRequestConfig<MarketComparisonResponseDefinition>,
          Record<string, never>
        >(
          getUrl(MarketApis.COMPARE_LISTING, {
            marketBoard: listingId,
            character: characterId,
          }),
          {},
          { signal: controller.signal }
        );

        setData(result);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        setError({
          message: resolveApiErrorMessage(
            requestError,
            'Unable to compare this listing.'
          ),
        });

        if (requestError instanceof AxiosError) {
          handleInactivity({ response: requestError, setError });
        }
      } finally {
        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    };

    void fetchComparison();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, handleInactivity, listingId, characterId]);

  return { data, loading, error };
};
