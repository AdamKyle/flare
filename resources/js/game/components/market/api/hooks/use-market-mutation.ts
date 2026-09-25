import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseMarketMutationDefinition from './definitions/use-market-mutation-definition';

export const useMarketMutation = <TResponse>(
  fallbackErrorMessage: string
): UseMarketMutationDefinition<TResponse> => {
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] =
    useState<UseMarketMutationDefinition<TResponse>['error']>(null);

  const isSubmittingRef = useRef(false);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const clearError = useCallback(() => {
    setError(null);
  }, []);

  const run = useCallback(
    async (
      execute: (signal: AbortSignal) => Promise<TResponse>
    ): Promise<TResponse | null> => {
      if (isSubmittingRef.current) {
        return null;
      }

      isSubmittingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setLoading(true);
      setError(null);

      try {
        return await execute(controller.signal);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return null;
        }

        setError({
          message: resolveApiErrorMessage(requestError, fallbackErrorMessage),
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
    [handleInactivity, fallbackErrorMessage]
  );

  return { loading, error, run, clear_error: clearError };
};
