import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import UseMoveNpcDefinition from './definitions/use-move-npc-definition';
import { parseValidationErrors } from '../../../../utils/parse-validation-errors';
import MoveNpcRequestDefinition from '../definitions/move-npc-request-definition';
import NpcDefinition from '../definitions/npc-definition';
import { NpcApiMessages } from '../enums/npc-api-messages';
import { NpcApiUrls } from '../enums/npc-api-urls';

export const useMoveNpc = (): UseMoveNpcDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [moving, setMoving] = useState(false);
  const [error, setError] = useState<UseMoveNpcDefinition['error']>(null);
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
      npcId: number,
      request: MoveNpcRequestDefinition
    ): Promise<NpcDefinition | null> => {
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
          NpcDefinition,
          Record<string, never>,
          MoveNpcRequestDefinition
        >(
          getUrl(NpcApiUrls.MOVE, { gameMap: gameMapId, npc: npcId }),
          request,
          {
            signal: controller.signal,
          }
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
            message: responseData?.message ?? NpcApiMessages.MoveFallback,
          });

          return null;
        }

        setError({ message: NpcApiMessages.MoveFallback });

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
