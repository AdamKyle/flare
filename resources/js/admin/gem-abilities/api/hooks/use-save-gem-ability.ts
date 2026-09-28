import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseSaveGemAbilityDefinition from './definitions/use-save-gem-ability-definition';
import { parseValidationErrors } from '../../../../utils/parse-validation-errors';
import GemAbilityFormDefinition from '../definitions/gem-ability-form-definition';
import GemAbilityRequestDefinition from '../definitions/gem-ability-request-definition';
import { GemAbilityApiMessages } from '../enums/gem-ability-api-messages';
import { GemAbilityApiUrls } from '../enums/gem-ability-api-urls';

export const useSaveGemAbility = (): UseSaveGemAbilityDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
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
    gemAbilityId: number | null,
    payload: GemAbilityRequestDefinition
  ): Promise<GemAbilityFormDefinition | null> => {
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
      gemAbilityId === null
        ? GemAbilityApiMessages.CreateFallback
        : GemAbilityApiMessages.UpdateFallback;

    try {
      if (gemAbilityId === null) {
        return await apiHandler.post<
          GemAbilityFormDefinition,
          Record<string, never>,
          GemAbilityRequestDefinition
        >(getUrl(GemAbilityApiUrls.LIST), payload, {
          signal: controller.signal,
        });
      }

      return await apiHandler.put<
        GemAbilityFormDefinition,
        Record<string, never>,
        GemAbilityRequestDefinition
      >(
        getUrl(GemAbilityApiUrls.SHOW, { gameGemAbility: gemAbilityId }),
        payload,
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
