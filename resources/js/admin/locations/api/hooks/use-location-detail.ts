import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import UseLocationDetailDefinition from './definitions/use-location-detail-definition';
import LocationDetailDefinition from '../definitions/location-detail-definition';
import { LocationApiMessages } from '../enums/location-api-messages';
import { LocationApiUrls } from '../enums/location-api-urls';

export const useLocationDetail = (
  locationId: number
): UseLocationDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [location, setLocation] = useState<LocationDetailDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchLocation = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        LocationDetailDefinition,
        Record<string, never>
      >(getUrl(LocationApiUrls.DETAIL, { location: locationId }), {
        signal: controller.signal,
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setLocation(result);
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

      setError({ message: LocationApiMessages.Load });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, locationId]);

  useEffect(() => {
    void fetchLocation();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchLocation, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return {
    location,
    loading,
    error,
    refresh,
  };
};
