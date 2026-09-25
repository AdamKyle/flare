import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseFetchExplorationOutputDefinition from './definitions/use-fetch-exploration-output-definition';
import { ExplorationApiUrls } from '../../../api/enums/exploration-api-urls';
import ExplorationOutputResponseDefinition from '../../types/exploration-output-response-definition';

export const useFetchExplorationOutput = (
  characterId: number
): UseFetchExplorationOutputDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<ExplorationOutputResponseDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const abortControllerRef = useRef<AbortController | null>(null);
  const requestGenerationRef = useRef(0);

  const url = getUrl(ExplorationApiUrls.EXPLORATION_OUTPUT, {
    character: characterId,
  });

  const fetchOutput = useCallback(async () => {
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
        ExplorationOutputResponseDefinition,
        AxiosRequestConfig<ExplorationOutputResponseDefinition>
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

      setError('Unable to load your Exploration status.');
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
    void fetchOutput();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchOutput, refreshToken]);

  const refetch = useCallback(() => {
    setRefreshToken((previous) => previous + 1);
  }, []);

  return { data, loading, error, refetch };
};
