import {useApiHandler} from "api-handler/hooks/use-api-handler";
import { useCallback, useEffect, useState } from 'react';
import {MarketApis} from "../enums/market-apis";
import UseGetMarketHistoryForTypeRequestParams from "../definitions/use-get-market-history-for-type-request-params";
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import {useActivityTimeout} from "api-handler/hooks/use-activity-timeout";
import UseGetMarketHistoryForTypeDefinition from "../definitions/use-get-market-history-for-type-definition";
import MarketHistoryForTypeResponseDefinition from "../definitions/use-get-market-history-for-type-response-definition";

export const useGetMarketHistoryForType = (): UseGetMarketHistoryForTypeDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] =
    useState<MarketHistoryForTypeResponseDefinition[] | []>([]);
  const [error, setError] = useState<
    UseGetMarketHistoryForTypeDefinition['error'] | null
  >(null);
  const [loading, setLoading] = useState(true);
  const [requestParams, setRequestParams] =
    useState<UseGetMarketHistoryForTypeRequestParams>({
      type: null,
      filter: null,
    });

  const url = getUrl(MarketApis.MARKET_HISTORY_FOR_TYPE);

  const fetchMarketHistoryForType = useCallback(async () => {
    if (!requestParams.type) {
      return;
    }

    try {
      const result = await apiHandler.get<
        MarketHistoryForTypeResponseDefinition[],
        AxiosRequestConfig<
          AxiosResponse<MarketHistoryForTypeResponseDefinition[]>
        >
      >(url, {
        params: {
          type: requestParams.type,
          filter: requestParams.filter,
        },
      });

      console.log(result);

      setData(result);
    } catch (err) {
      if (err instanceof AxiosError) {
        handleInactivity({
          setError: setError,
          response: err,
        });

        setError(err.response?.data || null);
      }
    } finally {
      setLoading(false);
    }
  }, [url, apiHandler, requestParams]);

  useEffect(() => {
    fetchMarketHistoryForType().catch(() => {});
  }, [fetchMarketHistoryForType]);

  return {
    data,
    loading,
    error,
    setRequestParams,
  }
};