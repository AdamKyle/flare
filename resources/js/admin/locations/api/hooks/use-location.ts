import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import UseLocationDefinition from './definitions/use-location-definition';
import LocationDefinition from '../definitions/location-definition';
import { LocationApiMessages } from '../enums/location-api-messages';
import { LocationApiUrls } from '../enums/location-api-urls';

export const useLocation = (
  gameMapId: number,
  locationId: number | null
): UseLocationDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [location, setLocation] = useState<LocationDefinition | null>(null);
  const [loading, setLoading] = useState(locationId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (locationId === null) {
      setLocation(null);
      setLoading(false);
      setError(null);

      return;
    }

    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchLocation = async () => {
      try {
        const result = await apiHandler.get<
          LocationDefinition,
          Record<string, never>
        >(
          getUrl(LocationApiUrls.SHOW, {
            gameMap: gameMapId,
            location: locationId,
          }),
          {
            signal: controller.signal,
          }
        );

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
    };

    void fetchLocation();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, gameMapId, locationId]);

  return { location, loading, error };
};
