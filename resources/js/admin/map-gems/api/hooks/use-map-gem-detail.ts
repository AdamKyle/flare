import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseMapGemDetailDefinition from './definitions/use-map-gem-detail-definition';
import MapGemDetailDefinition from '../definitions/map-gem-detail-definition';
import { MapGemApiMessages } from '../enums/map-gem-api-messages';
import { MapGemApiUrls } from '../enums/map-gem-api-urls';

export const useMapGemDetail = (
  mapGemId: number
): UseMapGemDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [mapGem, setMapGem] = useState<MapGemDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchMapGem = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        MapGemDetailDefinition,
        Record<string, never>
      >(getUrl(MapGemApiUrls.SHOW, { gameMapGemParamter: mapGemId }), {
        signal: controller.signal,
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setMapGem(result);
    } catch (errorInstance) {
      if (axios.isCancel(errorInstance)) {
        return;
      }

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      if (axios.isAxiosError<{ message?: string }>(errorInstance)) {
        setError({
          message:
            errorInstance.response?.data?.message ?? errorInstance.message,
        });

        return;
      }

      setError({ message: MapGemApiMessages.Load });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, mapGemId]);

  useEffect(() => {
    void fetchMapGem();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchMapGem, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return { map_gem: mapGem, loading, error, refresh };
};
