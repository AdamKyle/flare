import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseActivateMapGemRollDefinition from './definitions/use-activate-map-gem-roll-definition';
import MapGemDetailDefinition from '../definitions/map-gem-detail-definition';
import { MapGemApiMessages } from '../enums/map-gem-api-messages';
import { MapGemApiUrls } from '../enums/map-gem-api-urls';

export const useActivateMapGemRoll = (): UseActivateMapGemRollDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [activating, setActivating] = useState(false);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const activatingRef = useRef(false);
  const mountedRef = useRef(true);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(
    () => () => {
      mountedRef.current = false;
      abortControllerRef.current?.abort();
    },
    []
  );

  const activateRoll = async (
    mapGemId: number,
    gemId: number
  ): Promise<MapGemDetailDefinition | null> => {
    if (activatingRef.current) {
      return null;
    }

    activatingRef.current = true;
    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;
    setActivating(true);
    setError(null);

    try {
      return await apiHandler.put<
        MapGemDetailDefinition,
        Record<string, never>,
        Record<string, never>
      >(
        getUrl(MapGemApiUrls.ACTIVATE_ROLL, {
          gameMapGemParamter: mapGemId,
          gem: gemId,
        }),
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
            MapGemApiMessages.ActivateRollFallback,
        });

        return null;
      }

      setError({ message: MapGemApiMessages.ActivateRollFallback });

      return null;
    } finally {
      activatingRef.current = false;
      if (mountedRef.current) {
        setActivating(false);
      }
    }
  };

  return { activating, error, activate_roll: activateRoll };
};
