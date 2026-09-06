import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseMapGemForEditDefinition from './definitions/use-map-gem-for-edit-definition';
import MapGemFormDefinition from '../definitions/map-gem-form-definition';
import { MapGemApiMessages } from '../enums/map-gem-api-messages';
import { MapGemApiUrls } from '../enums/map-gem-api-urls';

export const useMapGemForEdit = (
  mapGemId: number | null
): UseMapGemForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [mapGem, setMapGem] = useState<MapGemFormDefinition | null>(null);
  const [loading, setLoading] = useState(mapGemId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (mapGemId === null) {
      setLoading(false);

      return;
    }

    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchMapGem = async () => {
      try {
        const result = await apiHandler.get<
          MapGemFormDefinition,
          Record<string, never>
        >(getUrl(MapGemApiUrls.EDIT, { gameMapGemParamter: mapGemId }), {
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
    };

    void fetchMapGem();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, mapGemId]);

  return { map_gem: mapGem, loading, error };
};
