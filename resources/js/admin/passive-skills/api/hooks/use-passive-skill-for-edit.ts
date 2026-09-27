import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UsePassiveSkillForEditDefinition from './definitions/use-passive-skill-for-edit-definition';
import PassiveSkillFormDefinition from '../definitions/passive-skill-form-definition';
import { PassiveSkillApiMessages } from '../enums/passive-skill-api-messages';
import { PassiveSkillApiUrls } from '../enums/passive-skill-api-urls';

export const usePassiveSkillForEdit = (
  passiveSkillId: number | null
): UsePassiveSkillForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [passiveSkill, setPassiveSkill] =
    useState<PassiveSkillFormDefinition | null>(null);
  const [loading, setLoading] = useState(passiveSkillId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (passiveSkillId === null) {
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

    const fetchPassiveSkill = async () => {
      try {
        const result = await apiHandler.get<
          PassiveSkillFormDefinition,
          Record<string, never>
        >(getUrl(PassiveSkillApiUrls.EDIT, { passiveSkill: passiveSkillId }), {
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
