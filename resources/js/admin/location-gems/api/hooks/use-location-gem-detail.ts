import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseLocationGemDetailDefinition from './definitions/use-location-gem-detail-definition';
import LocationGemDetailDefinition from '../definitions/location-gem-detail-definition';
import { LocationGemApiMessages } from '../enums/location-gem-api-messages';
import { LocationGemApiUrls } from '../enums/location-gem-api-urls';

export const useLocationGemDetail = (
  locationGemId: number
): UseLocationGemDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [locationGem, setLocationGem] =
    useState<LocationGemDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchLocationGem = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        LocationGemDetailDefinition,
        Record<string, never>
      >(
        getUrl(LocationGemApiUrls.SHOW, {
          gameLocationGemParamter: locationGemId,
        }),
        { signal: controller.signal }
      );

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setLocationGem(result);
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

      setError({ message: LocationGemApiMessages.Load });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, locationGemId]);

  useEffect(() => {
    void fetchLocationGem();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchLocationGem, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return { location_gem: locationGem, loading, error, refresh };
};
