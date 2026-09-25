import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError } from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseGetMarketHistoryForTypeDefinition from '../definitions/use-get-market-history-for-type-definition';
import UseGetMarketHistoryForTypeRequestParams from '../definitions/use-get-market-history-for-type-request-params';
import MarketHistoryForTypeResponseDefinition from '../definitions/use-get-market-history-for-type-response-definition';
import { MarketApis } from '../enums/market-apis';

export const useGetMarketHistoryForType = ({
  type,
  filter,
}: UseGetMarketHistoryForTypeRequestParams): UseGetMarketHistoryForTypeDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] = useState<MarketHistoryForTypeResponseDefinition[]>(
    []
  );
  const [error, setError] =
    useState<UseGetMarketHistoryForTypeDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchMarketHistory = async () => {
      try {
        const result = await apiHandler.get<
          MarketHistoryForTypeResponseDefinition[],
          UseGetMarketHistoryForTypeRequestParams
        >(getUrl(MarketApis.MARKET_HISTORY_FOR_TYPE), {
          params: { type, filter },
          signal: controller.signal,
        });

        setData(result);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        setError({
          message: resolveApiErrorMessage(
            requestError,
            'Unable to load the Market history.'
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

    void fetchMarketHistory();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, handleInactivity, type, filter]);

  return { data, loading, error };
};
