import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseActivateLocationGemRollDefinition from './definitions/use-activate-location-gem-roll-definition';
import LocationGemDetailDefinition from '../definitions/location-gem-detail-definition';
import { LocationGemApiMessages } from '../enums/location-gem-api-messages';
import { LocationGemApiUrls } from '../enums/location-gem-api-urls';

export const useActivateLocationGemRoll =
  (): UseActivateLocationGemRollDefinition => {
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
      locationGemId: number,
      gemId: number
    ): Promise<LocationGemDetailDefinition | null> => {
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
          LocationGemDetailDefinition,
          Record<string, never>,
          Record<string, never>
        >(
          getUrl(LocationGemApiUrls.ACTIVATE_ROLL, {
            gameLocationGemParamter: locationGemId,
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
              LocationGemApiMessages.ActivateRollFallback,
          });

          return null;
        }

        setError({ message: LocationGemApiMessages.ActivateRollFallback });

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
