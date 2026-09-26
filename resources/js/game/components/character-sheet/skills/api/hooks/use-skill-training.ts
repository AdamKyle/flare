import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseSkillTrainingDefinition from './definitions/use-skill-training-definition';
import SkillTrainingResponseDefinition from '../definitions/skill-training-response-definition';
import TrainSkillRequestDefinition from '../definitions/train-skill-request-definition';
import { SkillsApiUrls } from '../enums/skills-api-urls';

export const useSkillTraining = (
  characterId: number
): UseSkillTrainingDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);
  const isSubmittingRef = useRef(false);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const submit = useCallback(
    async <TRequest extends object>(
      url: string,
      data: TRequest,
      fallbackMessage: string
    ): Promise<SkillTrainingResponseDefinition | null> => {
      if (isSubmittingRef.current) {
        return null;
      }

      isSubmittingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setSubmitting(true);
      setError(null);

      try {
        return await apiHandler.post<
          SkillTrainingResponseDefinition,
          AxiosRequestConfig<SkillTrainingResponseDefinition>,
          TRequest
        >(url, data, { signal: controller.signal });
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return null;
        }

        setError(resolveApiErrorMessage(requestError, fallbackMessage));

        return null;
      } finally {
        isSubmittingRef.current = false;
        abortControllerRef.current = null;
        setSubmitting(false);
      }
    },
    [apiHandler]
  );

  const train = useCallback(
    (skillId: number, xpPercentage: number) =>
      submit<TrainSkillRequestDefinition>(
        getUrl(SkillsApiUrls.TRAIN, { character: characterId }),
        { skill_id: skillId, xp_percentage: xpPercentage },
        'Unable to train this Skill.'
      ),
    [submit, getUrl, characterId]
  );

  const cancel = useCallback(
    (skillId: number) =>
      submit<Record<string, never>>(
        getUrl(SkillsApiUrls.CANCEL_TRAIN, {
          character: characterId,
          skill: skillId,
        }),
        {},
        'Unable to stop training this Skill.'
      ),
    [submit, getUrl, characterId]
  );

  return { submitting, error, train, cancel };
};
