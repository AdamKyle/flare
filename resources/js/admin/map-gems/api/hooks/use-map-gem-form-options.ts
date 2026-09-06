import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseMapGemFormOptionsDefinition from './definitions/use-map-gem-form-options-definition';
import MapGemFormOptionsDefinition from '../definitions/map-gem-form-options-definition';
import { MapGemApiMessages } from '../enums/map-gem-api-messages';
import { MapGemApiUrls } from '../enums/map-gem-api-urls';

export const useMapGemFormOptions = (): UseMapGemFormOptionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [formOptions, setFormOptions] =
    useState<MapGemFormOptionsDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchFormOptions = async () => {
      try {
        const result = await apiHandler.get<
          MapGemFormOptionsDefinition,
          Record<string, never>
        >(getUrl(MapGemApiUrls.OPTIONS), { signal: controller.signal });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setFormOptions(result);
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

        setError({ message: MapGemApiMessages.LoadFormOptions });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchFormOptions();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl]);

  return { form_options: formOptions, loading, error };
};
