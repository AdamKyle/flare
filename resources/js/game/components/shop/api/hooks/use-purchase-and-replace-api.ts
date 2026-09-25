import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UsePurchaseAndReplaceApiDefinition from './definitions/use-purchase-and-replace-api-definition';
import UsePurchaseAndReplaceApiParams from './definitions/use-purchase-and-replace-api-params';
import UsePurchaseAndReplaceApiRequestDefinition from './definitions/use-purchase-and-replace-api-request-definition';
import UsePurchaseAndReplaceApiResponseDefinition from './definitions/use-purchase-and-replace-api-response-definition';
import { ShopApiUrls } from '../enums/shop-api-urls';

export const usePurchaseAndReplaceApi = ({
  character_id,
  on_success,
}: UsePurchaseAndReplaceApiParams): UsePurchaseAndReplaceApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [error, setError] =
    useState<UsePurchaseAndReplaceApiDefinition['error']>(null);
  const [loading, setLoading] = useState(false);

  const isSubmittingRef = useRef(false);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const mutate = useCallback(
    async (
      request: UsePurchaseAndReplaceApiRequestDefinition
    ): Promise<UsePurchaseAndReplaceApiResponseDefinition | null> => {
      if (isSubmittingRef.current || character_id <= 0) {
        return null;
      }

      isSubmittingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.post<
          UsePurchaseAndReplaceApiResponseDefinition,
          AxiosRequestConfig<UsePurchaseAndReplaceApiResponseDefinition>,
          UsePurchaseAndReplaceApiRequestDefinition
        >(
          getUrl(ShopApiUrls.BUY_AND_REPLACE, { character: character_id }),
          request,
          { signal: controller.signal }
        );

        on_success(result.message, {
          gold: result.gold,
          inventory_count: result.inventory_count,
        });

        return result;
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return null;
        }

        setError({
          message: resolveApiErrorMessage(
            requestError,
            'Unable to purchase and replace this item.'
          ),
        });

        if (requestError instanceof AxiosError) {
          handleInactivity({ response: requestError, setError });
        }

        return null;
      } finally {
        isSubmittingRef.current = false;

        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    },
    [apiHandler, getUrl, handleInactivity, character_id, on_success]
  );

  return {
    error,
    loading,
    mutate,
  };
};
