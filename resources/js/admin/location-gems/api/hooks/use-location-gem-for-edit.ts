import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseLocationGemForEditDefinition from './definitions/use-location-gem-for-edit-definition';
import LocationGemFormDefinition from '../definitions/location-gem-form-definition';
import { LocationGemApiMessages } from '../enums/location-gem-api-messages';
import { LocationGemApiUrls } from '../enums/location-gem-api-urls';

export const useLocationGemForEdit = (
  locationGemId: number | null
): UseLocationGemForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [locationGem, setLocationGem] =
    useState<LocationGemFormDefinition | null>(null);
  const [loading, setLoading] = useState(locationGemId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (locationGemId === null) {
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

    const fetchLocationGem = async () => {
      try {
        const result = await apiHandler.get<
          LocationGemFormDefinition,
          Record<string, never>
        >(
          getUrl(LocationGemApiUrls.EDIT, {
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
    };

    void fetchLocationGem();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, locationGemId]);

  return { location_gem: locationGem, loading, error };
};
