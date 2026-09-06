import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseRollLocationGemDefinition from './definitions/use-roll-location-gem-definition';
import LocationGemDetailDefinition from '../definitions/location-gem-detail-definition';
import { LocationGemApiMessages } from '../enums/location-gem-api-messages';
import { LocationGemApiUrls } from '../enums/location-gem-api-urls';

export const useRollLocationGem = (): UseRollLocationGemDefinition => {
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
    locationGemId: number
  ): Promise<LocationGemDetailDefinition | null> => {
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
        LocationGemDetailDefinition,
        Record<string, never>,
        Record<string, never>
      >(
        getUrl(LocationGemApiUrls.ROLL, {
          gameLocationGemParamter: locationGemId,
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
            LocationGemApiMessages.RollFallback,
        });

        return null;
      }

      setError({ message: LocationGemApiMessages.RollFallback });

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
