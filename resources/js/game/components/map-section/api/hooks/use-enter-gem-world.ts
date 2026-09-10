import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { GemWorldApiUrls } from '../enums/gem-world-api-urls';
import UseGemWorldMutationDefinition from './definitions/use-gem-world-mutation-definition';

export const useEnterGemWorld = (
  characterId: number
): UseGemWorldMutationDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);
  const isSubmittingRef = useRef(false);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const enter = useCallback(async (): Promise<boolean> => {
    if (isSubmittingRef.current) {
      return false;
    }

    isSubmittingRef.current = true;
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      await apiHandler.post<
        { message: string },
        AxiosRequestConfig<{ message: string }>,
        Record<string, never>
      >(
        getUrl(GemWorldApiUrls.ENTER, { character: characterId }),
        {},
        { signal: controller.signal }
      );

      return true;
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return false;
      }

      setError('Unable to enter the Gem World.');

      return false;
    } finally {
      isSubmittingRef.current = false;
      abortControllerRef.current = null;
      setLoading(false);
    }
  }, [apiHandler, getUrl, characterId]);

  return { loading, error, action: enter };
};
