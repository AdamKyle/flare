import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError } from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseCompareItemApiDefinition from './definitions/use-compare-item-api-definition';
import UseCompareItemApiQueryDefinition from './definitions/use-compare-item-api-query-definition';
import UseCompareItemApiRequestParameters from './definitions/use-compare-item-api-request-params';
import { UseCompareItemApiResponseDefinition } from './definitions/use-compare-item-api-response-definition';

export const useCompareItemApi = (
  params: UseCompareItemApiRequestParameters
): UseCompareItemApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(true);
  const [data, setData] = useState<UseCompareItemApiDefinition['data']>(null);
  const [error, setError] =
    useState<UseCompareItemApiDefinition['error']>(null);

  const abortControllerRef = useRef<AbortController | null>(null);

  const characterId = params.characterData?.id ?? 0;
  const { url, item_name: itemName, item_type: itemType } = params;

  useEffect(() => {
    if (characterId <= 0) {
      setLoading(false);

      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchComparisonData = async () => {
      try {
        const result = await apiHandler.get<
          UseCompareItemApiResponseDefinition,
          UseCompareItemApiQueryDefinition
        >(getUrl(url, { character: characterId }), {
          params: {
            item_type: itemType,
            item_name: itemName,
          },
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
            'Unable to load the item comparison.'
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

    void fetchComparisonData();

    return () => {
      controller.abort();
    };
  }, [
    apiHandler,
    getUrl,
    handleInactivity,
    url,
    characterId,
    itemType,
    itemName,
  ]);

  return {
    loading,
    data,
    error,
  };
};
