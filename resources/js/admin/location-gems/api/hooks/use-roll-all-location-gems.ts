import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseRollAllLocationGemsDefinition from './definitions/use-roll-all-location-gems-definition';
import LocationGemBulkRollResultDefinition from '../definitions/location-gem-bulk-roll-result-definition';
import { LocationGemApiMessages } from '../enums/location-gem-api-messages';
import { LocationGemApiUrls } from '../enums/location-gem-api-urls';

export const useRollAllLocationGems = (): UseRollAllLocationGemsDefinition => {
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

  const rollAll =
    async (): Promise<LocationGemBulkRollResultDefinition | null> => {
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
          LocationGemBulkRollResultDefinition,
          Record<string, never>,
          Record<string, never>
        >(
          getUrl(LocationGemApiUrls.ROLL_ALL, {}),
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
              LocationGemApiMessages.RollAllFallback,
          });

          return null;
        }

        setError({ message: LocationGemApiMessages.RollAllFallback });

        return null;
      } finally {
        rollingRef.current = false;
        if (mountedRef.current) {
          setRolling(false);
        }
      }
    };

  return { rolling, error, roll_all: rollAll };
};
