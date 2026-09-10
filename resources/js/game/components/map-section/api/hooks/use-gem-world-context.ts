import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import GemWorldStatusDefinition from '../definitions/gem-world-status-definition';
import { GemWorldApiUrls } from '../enums/gem-world-api-urls';
import UseGemWorldContextDefinition from './definitions/use-gem-world-context-definition';
import UseGemWorldContextParams from './definitions/use-gem-world-context-params';

export const useGemWorldContext = ({
  character_id: characterId,
  game_map_id: gameMapId,
  x,
  y,
}: UseGemWorldContextParams): UseGemWorldContextDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<GemWorldStatusDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);
  const requestGenerationRef = useRef(0);

  const url = getUrl(GemWorldApiUrls.CONTEXT, { character: characterId });

  const fetchGemWorldContext = useCallback(async () => {
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
        GemWorldStatusDefinition,
        AxiosRequestConfig<GemWorldStatusDefinition>
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

      setError('Unable to load Gem World context.');
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
    void fetchGemWorldContext();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchGemWorldContext, gameMapId, x, y]);

  return { data, loading, error };
};
