import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import UseMoveLocationDefinition from './definitions/use-move-location-definition';
import { parseValidationErrors } from '../../../../utils/parse-validation-errors';
import LocationDefinition from '../definitions/location-definition';
import MoveLocationRequestDefinition from '../definitions/move-location-request-definition';
import { LocationApiMessages } from '../enums/location-api-messages';
import { LocationApiUrls } from '../enums/location-api-urls';

export const useMoveLocation = (): UseMoveLocationDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [moving, setMoving] = useState(false);
  const [error, setError] = useState<UseMoveLocationDefinition['error']>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const movingRef = useRef(false);
  const isMountedRef = useRef(true);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
      abortControllerRef.current?.abort();
    };
  }, []);

  const clearError = useCallback((): void => {
    setError(null);
    setFieldErrors({});
  }, []);

  const move = useCallback(
    async (
      gameMapId: number,
      locationId: number,
      request: MoveLocationRequestDefinition
    ): Promise<LocationDefinition | null> => {
      if (movingRef.current) {
        return null;
      }

      movingRef.current = true;
      setMoving(true);
      setError(null);
      setFieldErrors({});

      const controller = new AbortController();
      abortControllerRef.current = controller;

      try {
        const result = await apiHandler.patch<
          LocationDefinition,
          Record<string, never>,
          MoveLocationRequestDefinition
        >(
          getUrl(LocationApiUrls.MOVE, {
            gameMap: gameMapId,
            location: locationId,
          }),
          request,
          { signal: controller.signal }
        );

        if (!isMountedRef.current) {
          return null;
        }

        return result;
      } catch (errorInstance) {
        if (axios.isCancel(errorInstance)) {
          return null;
        }

        if (!isMountedRef.current) {
          return null;
        }

        if (
          axios.isAxiosError<{
            message?: string;
            errors?: Record<string, string[]>;
          }>(errorInstance)
        ) {
          if (errorInstance.response?.status === 401) {
            handleInactivity({ setError, response: errorInstance });

            return null;
          }

          const responseData = errorInstance.response?.data;

          setFieldErrors(parseValidationErrors(responseData));
          setError({
            message: responseData?.message ?? LocationApiMessages.MoveFallback,
          });

          return null;
        }

        setError({ message: LocationApiMessages.MoveFallback });

        return null;
      } finally {
        movingRef.current = false;

        if (isMountedRef.current) {
          setMoving(false);
        }
      }
    },
    [apiHandler, getUrl, handleInactivity]
  );

  return useMemo(
    () => ({
      moving,
      error,
      field_errors: fieldErrors,
      move,
      clear_error: clearError,
    }),
    [clearError, error, fieldErrors, move, moving]
  );
};
