import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseSaveUnitDefinition from './definitions/use-save-unit-definition';
import { parseValidationErrors } from '../../../../../utils/parse-validation-errors';
import UnitFormDefinition from '../definitions/unit-form-definition';
import UnitRequestDefinition from '../definitions/unit-request-definition';
import { UnitApiMessages } from '../enums/unit-api-messages';
import { UnitApiUrls } from '../enums/unit-api-urls';

export const useSaveUnit = (): UseSaveUnitDefinition => {
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
    unitId: number | null,
    payload: UnitRequestDefinition
  ): Promise<UnitFormDefinition | null> => {
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
      unitId === null
        ? UnitApiMessages.CreateFallback
        : UnitApiMessages.UpdateFallback;

    try {
      if (unitId === null) {
        return await apiHandler.post<
          UnitFormDefinition,
          Record<string, never>,
          UnitRequestDefinition
        >(getUrl(UnitApiUrls.LIST), payload, { signal: controller.signal });
      }

      return await apiHandler.put<
        UnitFormDefinition,
        Record<string, never>,
        UnitRequestDefinition
      >(getUrl(UnitApiUrls.SHOW, { gameUnit: unitId }), payload, {
        signal: controller.signal,
      });
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
