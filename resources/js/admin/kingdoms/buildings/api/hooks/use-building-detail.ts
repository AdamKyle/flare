import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseBuildingDetailDefinition from './definitions/use-building-detail-definition';
import BuildingDetailDefinition from '../definitions/building-detail-definition';
import { BuildingApiMessages } from '../enums/building-api-messages';
import { BuildingApiUrls } from '../enums/building-api-urls';

export const useBuildingDetail = (
  buildingId: number
): UseBuildingDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [building, setBuilding] = useState<BuildingDetailDefinition | null>(
    null
  );
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

    const fetchBuilding = async () => {
      try {
        const result = await apiHandler.get<
          BuildingDetailDefinition,
          Record<string, never>
        >(getUrl(BuildingApiUrls.SHOW, { gameBuilding: buildingId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setBuilding(result);
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

        setError({ message: BuildingApiMessages.Load });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchBuilding();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, buildingId]);

  return { building, loading, error };
};
