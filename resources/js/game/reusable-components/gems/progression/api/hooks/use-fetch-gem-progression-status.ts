import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseFetchGemProgressionStatusDefinition from './definitions/use-fetch-gem-progression-status-definition';
import GemProgressionStatusDefinition from '../definitions/gem-progression-status-definition';
import { GemProgressionApiUrls } from '../enums/gem-progression-api-urls';

export const useFetchGemProgressionStatus = (
  characterId: number
): UseFetchGemProgressionStatusDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<GemProgressionStatusDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const abortControllerRef = useRef<AbortController | null>(null);
  const requestGenerationRef = useRef(0);

  const url = getUrl(GemProgressionApiUrls.CURRENT_STATUS, {
    character: characterId,
  });

  const fetchStatus = useCallback(async () => {
    if (characterId <= 0) {
      setLoading(false);

      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;
    const requestGeneration = ++requestGenerationRef.current;

    setLoading(true);

    try {
      const result = await apiHandler.get<
        GemProgressionStatusDefinition,
        AxiosRequestConfig<GemProgressionStatusDefinition>
      >(url, { signal: controller.signal });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setData(result);
      setError(null);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setError('Unable to load your Gem progression status.');
    } finally {
      if (
        requestGenerationRef.current === requestGeneration &&
        abortControllerRef.current === controller
      ) {
        abortControllerRef.current = null;
        setLoading(false);
      }
    }
  }, [apiHandler, url, characterId]);

  useEffect(() => {
    void fetchStatus();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchStatus, refreshToken]);

  const refetch = useCallback(() => {
    setRefreshToken((previous) => previous + 1);
  }, []);

  return { data, loading, error, refetch };
};
