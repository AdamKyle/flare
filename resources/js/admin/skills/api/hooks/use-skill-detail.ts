import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseSkillDetailDefinition from './definitions/use-skill-detail-definition';
import SkillDetailDefinition from '../definitions/skill-detail-definition';
import { SkillApiMessages } from '../enums/skill-api-messages';
import { SkillApiUrls } from '../enums/skill-api-urls';

export const useSkillDetail = (skillId: number): UseSkillDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [skill, setSkill] = useState<SkillDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchSkill = async () => {
      try {
        const result = await apiHandler.get<
          SkillDetailDefinition,
          Record<string, never>
        >(getUrl(SkillApiUrls.SHOW, { gameSkill: skillId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setSkill(result);
      } catch (errorInstance) {
        if (axios.isCancel(errorInstance)) {
          return;
        }

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        if (axios.isAxiosError<{ message?: string }>(errorInstance)) {
          setError({
            message:
              errorInstance.response?.data?.message ?? errorInstance.message,
          });

          return;
        }

        setError({ message: SkillApiMessages.Load });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchSkill();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, skillId]);

  return { skill, loading, error };
};
