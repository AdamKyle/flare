import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseDelveActionsDefinition from './definitions/use-delve-actions-definition';
import { DelveApiUrls } from '../enums/delve-api-urls';

export const useDelveActions = (
  characterId: number
): UseDelveActionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [stopping, setStopping] = useState(false);
  const [dismissing, setDismissing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);
  const isSubmittingRef = useRef(false);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const submit = useCallback(
    async (
      url: DelveApiUrls,
      setSubmitting: (submitting: boolean) => void,
      fallbackMessage: string
    ): Promise<boolean> => {
      if (isSubmittingRef.current || characterId <= 0) {
        return false;
      }

      isSubmittingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setSubmitting(true);
      setError(null);

      try {
        await apiHandler.post<
          unknown,
          AxiosRequestConfig<unknown>,
          Record<string, never>
        >(
          getUrl(url, { character: characterId }),
          {},
          {
            signal: controller.signal,
          }
        );

        return true;
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return false;
        }

        setError(resolveApiErrorMessage(requestError, fallbackMessage));

        return false;
      } finally {
        isSubmittingRef.current = false;
        abortControllerRef.current = null;
        setSubmitting(false);
      }
    },
    [apiHandler, getUrl, characterId]
  );

  const stop = useCallback(
    () =>
      submit(
        DelveApiUrls.STOP,
        setStopping,
        'Unable to stop Delve. Please try again.'
      ),
    [submit]
  );

  const dismiss = useCallback(
    () =>
      submit(
        DelveApiUrls.DISMISS,
        setDismissing,
        'Unable to dismiss the Delve results. Please try again.'
      ),
    [submit]
  );

  return { stopping, dismissing, error, stop, dismiss };
};
