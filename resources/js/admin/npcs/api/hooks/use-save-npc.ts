import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseSaveNpcDefinition from './definitions/use-save-npc-definition';
import { parseValidationErrors } from '../../../../utils/parse-validation-errors';
import NpcDefinition from '../definitions/npc-definition';
import NpcRequestDefinition from '../definitions/npc-request-definition';
import { NpcApiMessages } from '../enums/npc-api-messages';
import { NpcApiUrls } from '../enums/npc-api-urls';

export const useSaveNpc = (): UseSaveNpcDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<UseSaveNpcDefinition['error']>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const savingRef = useRef(false);
  const isMountedRef = useRef(true);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
      abortControllerRef.current?.abort();
    };
  }, []);

  const clearFieldError = (field: string): void => {
    setFieldErrors((previous) => {
      if (!(field in previous)) {
        return previous;
      }

      const next = { ...previous };

      delete next[field];

      return next;
    });
  };

  const save = async (
    gameMapId: number,
    npcId: number | null,
    request: NpcRequestDefinition
  ): Promise<NpcDefinition | null> => {
    if (savingRef.current) {
      return null;
    }

    savingRef.current = true;
    setSaving(true);
    setError(null);
    setFieldErrors({});

    const controller = new AbortController();
    abortControllerRef.current = controller;
    const fallbackMessage =
      npcId === null
        ? NpcApiMessages.CreateFallback
        : NpcApiMessages.UpdateFallback;

    try {
      if (npcId === null) {
        return await apiHandler.post<
          NpcDefinition,
          Record<string, never>,
          NpcRequestDefinition
        >(getUrl(NpcApiUrls.STORE, { gameMap: gameMapId }), request, {
          signal: controller.signal,
        });
      }

      return await apiHandler.put<
        NpcDefinition,
        Record<string, never>,
        NpcRequestDefinition
      >(
        getUrl(NpcApiUrls.UPDATE, { gameMap: gameMapId, npc: npcId }),
        request,
        { signal: controller.signal }
      );
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
          message: responseData?.message ?? fallbackMessage,
        });

        return null;
      }

      setError({ message: fallbackMessage });

      return null;
    } finally {
      savingRef.current = false;

      if (isMountedRef.current) {
        setSaving(false);
      }
    }
  };

  return {
    saving,
    error,
    field_errors: fieldErrors,
    save,
    clear_field_error: clearFieldError,
  };
};
