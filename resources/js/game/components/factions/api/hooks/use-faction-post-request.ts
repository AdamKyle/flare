import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseFactionPostRequestDefinition from './definitions/use-faction-post-request-definition';

export const useFactionPostRequest = (): UseFactionPostRequestDefinition => {
  const { apiHandler } = useApiHandler();

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);
  const isSubmittingRef = useRef(false);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const clearError = useCallback(() => {
    setError(null);
  }, []);

  const submit = useCallback(
    async <TResponse, TRequest extends object>(
      url: string,
      data: TRequest,
      fallbackMessage: string
    ): Promise<TResponse | null> => {
      if (isSubmittingRef.current) {
        return null;
      }

      isSubmittingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setSubmitting(true);
      setError(null);

      try {
        return await apiHandler.post<
          TResponse,
          AxiosRequestConfig<TResponse>,
          TRequest
        >(url, data, { signal: controller.signal });
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return null;
        }

        setError(resolveApiErrorMessage(requestError, fallbackMessage));

        return null;
      } finally {
        isSubmittingRef.current = false;
        abortControllerRef.current = null;
        setSubmitting(false);
      }
    },
    [apiHandler]
  );

  return { submitting, error, clear_error: clearError, submit };
};
