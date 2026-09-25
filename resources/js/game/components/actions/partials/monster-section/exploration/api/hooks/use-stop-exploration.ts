import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseStopExplorationDefinition from './definitions/use-stop-exploration-definition';
import { ExplorationApiUrls } from '../../../api/enums/exploration-api-urls';

export const useStopExploration = (
  characterId: number
): UseStopExplorationDefinition => {
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

  const stop = useCallback(async (): Promise<boolean> => {
    if (isSubmittingRef.current || characterId <= 0) {
      return false;
    }

    isSubmittingRef.current = true;
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      await apiHandler.post<
        Record<string, never>,
        AxiosRequestConfig<Record<string, never>>,
        Record<string, never>
      >(
        getUrl(ExplorationApiUrls.STOP_EXPLORATION, {
          character: characterId,
        }),
        {},
        { signal: controller.signal }
      );

      return true;
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return false;
      }

      setError('Unable to cancel Exploration. Please try again.');

      return false;
    } finally {
      isSubmittingRef.current = false;
      abortControllerRef.current = null;
      setLoading(false);
    }
  }, [apiHandler, getUrl, characterId]);

  return { loading, error, stop };
};
