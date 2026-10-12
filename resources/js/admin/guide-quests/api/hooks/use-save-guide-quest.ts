import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseSaveGuideQuestDefinition from './definitions/use-save-guide-quest-definition';
import { parseValidationErrors } from '../../../../utils/parse-validation-errors';
import GuideQuestFormErrorsDefinition from '../../definitions/guide-quest-form-errors-definition';
import GuideQuestFormStateDefinition from '../../definitions/guide-quest-form-state-definition';
import {
  buildGuideQuestFormData,
  guideQuestFormHasImages,
} from '../../utils/guide-quest-form-data-util';
import { normalizeGuideQuestValidationErrors } from '../../utils/guide-quest-form-state';
import GuideQuestResponseDefinition from '../definitions/guide-quest-response-definition';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';

const STRUCTURAL_ERROR =
  'The Guide Quest form submitted invalid data. Reload the editor and try again.';

export const useSaveGuideQuest = (): UseSaveGuideQuestDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [fieldErrors, setFieldErrors] =
    useState<GuideQuestFormErrorsDefinition>({});
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

  const clearFieldError = (
    field: keyof GuideQuestFormStateDefinition
  ): void => {
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
    guideQuestId: number,
    state: GuideQuestFormStateDefinition
  ) => {
    if (savingRef.current) {
      return null;
    }

    savingRef.current = true;
    setSaving(true);
    setError(null);
    setFieldErrors({});
    const controller = new AbortController();
    abortControllerRef.current = controller;
    const formData = buildGuideQuestFormData(state);
    formData.append('guide_quest_id', String(guideQuestId));
    formData.append('has_image', guideQuestFormHasImages(state) ? '1' : '0');

    try {
      const response = await apiHandler.post<
        GuideQuestResponseDefinition,
        AxiosRequestConfig,
        FormData
      >(getUrl(GuideQuestApiUrls.STORE_COMPONENT_CONTENT), formData, {
        signal: controller.signal,
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      return response.guide_quest;
    } catch (requestError) {
      if (axios.isCancel(requestError) || !isMountedRef.current) {
        return null;
      }

      if (axios.isAxiosError(requestError)) {
        if (requestError.response?.status === 401) {
          handleInactivity({ response: requestError, setError });
          return null;
        }

        const parsed = parseValidationErrors(requestError.response?.data);
        setFieldErrors(normalizeGuideQuestValidationErrors(parsed));
        setError({
          message:
            'content' in parsed
              ? STRUCTURAL_ERROR
              : (requestError.response?.data?.message ??
                'Unable to save the Guide Quest.'),
        });
        return null;
      }

      setError({ message: 'Unable to save the Guide Quest.' });
      return null;
    } finally {
      savingRef.current = false;
      abortControllerRef.current = null;
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
