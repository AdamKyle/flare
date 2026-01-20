import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import UseListItemOnMarketDefinition from '../definitions/use-list-item-on-market-definition';
import UseListItemOnMarketRequestDefinition from '../definitions/use-list-item-on-market-request-definition';
import UseListItemOnMarketRequestParamsDefinition from '../definitions/use-list-item-on-market-request-params-definition';
import UseListItemOnMarketResponseDefinition from '../definitions/use-list-item-on-market-response-definition';
import { MarketApis } from '../enums/market-apis';

export const UseListItemOnMarket = (): UseListItemOnMarketDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<
    UseListItemOnMarketDefinition['error'] | null
  >(null);
  const [requestParams, setRequestParams] =
    useState<UseListItemOnMarketRequestParamsDefinition>({
      character_id: 0,
      list_for: 0,
      slot_id: 0,
      on_success: () => {},
    });

  const listItemOnMarketPlace = useCallback(async () => {
    if (requestParams.character_id === 0 || requestParams.slot_id === 0) {
      return;
    }

    const url = getUrl(MarketApis.LIST_ITEM_ON_MARKET, {
      character: requestParams.character_id,
    });

    setLoading(true);

    try {
      const result = await apiHandler.post<
        UseListItemOnMarketResponseDefinition,
        AxiosRequestConfig<UseListItemOnMarketResponseDefinition>,
        UseListItemOnMarketRequestDefinition
      >(url, {
        slot_id: requestParams.slot_id,
        list_for: requestParams.list_for,
      });

      requestParams.on_success(result.message);
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
  }, [requestParams]);

  useEffect(() => {
    listItemOnMarketPlace().catch(() => {});
  }, [listItemOnMarketPlace]);

  return {
    loading,
    error,
    setRequestParams,
  };
};
