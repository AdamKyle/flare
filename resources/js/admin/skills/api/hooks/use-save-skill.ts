import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseSaveSkillDefinition from './definitions/use-save-skill-definition';
import { parseValidationErrors } from '../../../../utils/parse-validation-errors';
import SkillFormDefinition from '../definitions/skill-form-definition';
import SkillRequestDefinition from '../definitions/skill-request-definition';
import { SkillApiMessages } from '../enums/skill-api-messages';
import { SkillApiUrls } from '../enums/skill-api-urls';

export const useSaveSkill = (): UseSaveSkillDefinition => {
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
    skillId: number | null,
    payload: SkillRequestDefinition
  ): Promise<SkillFormDefinition | null> => {
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
      skillId === null
        ? SkillApiMessages.CreateFallback
        : SkillApiMessages.UpdateFallback;

    try {
      if (skillId === null) {
        return await apiHandler.post<
          SkillFormDefinition,
          Record<string, never>,
          SkillRequestDefinition
        >(getUrl(SkillApiUrls.LIST), payload, { signal: controller.signal });
      }

      return await apiHandler.put<
        SkillFormDefinition,
        Record<string, never>,
        SkillRequestDefinition
      >(getUrl(SkillApiUrls.SHOW, { gameSkill: skillId }), payload, {
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
