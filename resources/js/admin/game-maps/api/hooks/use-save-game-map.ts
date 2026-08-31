import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseSaveGameMapDefinition from './definitions/use-save-game-map-definition';
import { parseValidationErrors } from '../../../../utils/parse-validation-errors';
import GameMapFormResponseDefinition from '../../definitions/game-map-form-response-definition';
import { GameMapApiMessages } from '../enums/game-map-api-messages';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

export const useSaveGameMap = (): UseSaveGameMapDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<UseSaveGameMapDefinition['error']>(null);
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
    gameMapId: number | null,
    formData: FormData
  ): Promise<GameMapFormResponseDefinition | null> => {
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
      gameMapId === null
        ? GameMapApiMessages.CreateFallback
        : GameMapApiMessages.UpdateFallback;
    const url =
      gameMapId === null
        ? getUrl(GameMapApiUrls.LIST)
        : getUrl(GameMapApiUrls.SHOW, { gameMap: gameMapId });

    try {
      return await apiHandler.post<
        GameMapFormResponseDefinition,
        Record<string, never>,
        FormData
      >(url, formData, { signal: controller.signal });
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
