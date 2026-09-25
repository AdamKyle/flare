import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError } from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseMarketAccessDefinition from './definitions/use-market-access-definition';
import UseMarketAccessParams from './definitions/use-market-access-params';
import MarketAccessResponseDefinition from '../definitions/market-access-response-definition';
import { MarketApis } from '../enums/market-apis';

export const useMarketAccess = ({
  character_id,
  refresh_key,
}: UseMarketAccessParams): UseMarketAccessDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [canAccessMarket, setCanAccessMarket] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<UseMarketAccessDefinition['error']>(null);

  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (character_id <= 0) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchMarketAccess = async () => {
      try {
        const result = await apiHandler.get<
          MarketAccessResponseDefinition,
          Record<string, never>
        >(getUrl(MarketApis.MARKET_ACCESS, { character: character_id }), {
          signal: controller.signal,
        });

        setCanAccessMarket(result.can_access_market);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        setCanAccessMarket(false);
        setError({
          message: resolveApiErrorMessage(
            requestError,
            'Unable to check whether you can use the Market.'
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

    void fetchMarketAccess();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, handleInactivity, character_id, refresh_key]);

  return { can_access_market: canAccessMarket, loading, error };
};
