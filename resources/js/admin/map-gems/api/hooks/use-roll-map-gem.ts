import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseRollMapGemDefinition from './definitions/use-roll-map-gem-definition';
import MapGemDetailDefinition from '../definitions/map-gem-detail-definition';
import { MapGemApiMessages } from '../enums/map-gem-api-messages';
import { MapGemApiUrls } from '../enums/map-gem-api-urls';

export const useRollMapGem = (): UseRollMapGemDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [rolling, setRolling] = useState(false);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const rollingRef = useRef(false);
  const mountedRef = useRef(true);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(
    () => () => {
      mountedRef.current = false;
      abortControllerRef.current?.abort();
    },
    []
  );

  const roll = async (
    mapGemId: number
  ): Promise<MapGemDetailDefinition | null> => {
    if (rollingRef.current) {
      return null;
    }

    rollingRef.current = true;
    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;
    setRolling(true);
    setError(null);

    try {
      return await apiHandler.post<
        MapGemDetailDefinition,
        Record<string, never>,
        Record<string, never>
      >(
        getUrl(MapGemApiUrls.ROLL, { gameMapGemParamter: mapGemId }),
        {},
        { signal: controller.signal }
      );
    } catch (errorInstance) {
      if (axios.isCancel(errorInstance) || !mountedRef.current) {
        return null;
      }
      if (axios.isAxiosError<{ message?: string }>(errorInstance)) {
        setError({
          message:
            errorInstance.response?.data?.message ??
            MapGemApiMessages.RollFallback,
        });

        return null;
      }

      setError({ message: MapGemApiMessages.RollFallback });

      return null;
    } finally {
      rollingRef.current = false;
      if (mountedRef.current) {
        setRolling(false);
      }
    }
  };

  return { rolling, error, roll };
};
