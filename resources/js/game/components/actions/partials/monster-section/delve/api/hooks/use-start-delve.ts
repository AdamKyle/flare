import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseStartDelveDefinition from './definitions/use-start-delve-definition';
import DelveMessageResponseDefinition from '../definitions/delve-message-response-definition';
import StartDelveRequestDefinition from '../definitions/start-delve-request-definition';
import { DelveApiUrls } from '../enums/delve-api-urls';

export const useStartDelve = (characterId: number): UseStartDelveDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [starting, setStarting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);
  const isSubmittingRef = useRef(false);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const start = useCallback(
    async (request: StartDelveRequestDefinition): Promise<boolean> => {
      if (isSubmittingRef.current || characterId <= 0) {
        return false;
      }

      isSubmittingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setStarting(true);
      setError(null);

      try {
        await apiHandler.post<
          DelveMessageResponseDefinition,
          AxiosRequestConfig<DelveMessageResponseDefinition>,
          StartDelveRequestDefinition
        >(getUrl(DelveApiUrls.START, { character: characterId }), request, {
          signal: controller.signal,
        });

        return true;
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return false;
        }

        setError(
          resolveApiErrorMessage(
            requestError,
            'Unable to start Delve. Please try again.'
          )
        );

        return false;
      } finally {
        isSubmittingRef.current = false;
        abortControllerRef.current = null;
        setStarting(false);
      }
    },
    [apiHandler, getUrl, characterId]
  );

  return { starting, error, start };
};
