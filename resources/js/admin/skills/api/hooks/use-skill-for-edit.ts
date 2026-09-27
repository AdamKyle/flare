import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseSkillForEditDefinition from './definitions/use-skill-for-edit-definition';
import SkillFormDefinition from '../definitions/skill-form-definition';
import { SkillApiMessages } from '../enums/skill-api-messages';
import { SkillApiUrls } from '../enums/skill-api-urls';

export const useSkillForEdit = (
  skillId: number | null
): UseSkillForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [skill, setSkill] = useState<SkillFormDefinition | null>(null);
  const [loading, setLoading] = useState(skillId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (skillId === null) {
      setLoading(false);

      return;
    }

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
          SkillFormDefinition,
          Record<string, never>
        >(getUrl(SkillApiUrls.EDIT, { gameSkill: skillId }), {
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
