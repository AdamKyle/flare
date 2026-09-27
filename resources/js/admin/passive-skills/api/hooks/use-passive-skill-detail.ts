import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UsePassiveSkillDetailDefinition from './definitions/use-passive-skill-detail-definition';
import PassiveSkillDetailDefinition from '../definitions/passive-skill-detail-definition';
import { PassiveSkillApiMessages } from '../enums/passive-skill-api-messages';
import { PassiveSkillApiUrls } from '../enums/passive-skill-api-urls';

export const usePassiveSkillDetail = (
  passiveSkillId: number
): UsePassiveSkillDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [passiveSkill, setPassiveSkill] =
    useState<PassiveSkillDetailDefinition | null>(null);
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

    const fetchPassiveSkill = async () => {
      try {
        const result = await apiHandler.get<
          PassiveSkillDetailDefinition,
          Record<string, never>
        >(getUrl(PassiveSkillApiUrls.SHOW, { passiveSkill: passiveSkillId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setPassiveSkill(result);
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

        setError({ message: PassiveSkillApiMessages.Load });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchPassiveSkill();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, passiveSkillId]);

  return { passive_skill: passiveSkill, loading, error };
};
