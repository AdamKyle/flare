import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UsePassiveSkillTreeDefinition from './definitions/use-passive-skill-tree-definition';
import PassiveSkillTreeDefinition from '../definitions/passive-skill-tree-definition';
import { PassiveSkillApiMessages } from '../enums/passive-skill-api-messages';
import { PassiveSkillApiUrls } from '../enums/passive-skill-api-urls';

export const usePassiveSkillTree = (): UsePassiveSkillTreeDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [passiveSkills, setPassiveSkills] = useState<
    PassiveSkillTreeDefinition[]
  >([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const requestGenerationRef = useRef(0);

  useEffect(() => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;
    const controller = new AbortController();

    const fetchTree = async (): Promise<void> => {
      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.get<
          PassiveSkillTreeDefinition[],
          Record<string, never>
        >(getUrl(PassiveSkillApiUrls.TREE), { signal: controller.signal });

        if (requestGenerationRef.current === requestGeneration) {
          setPassiveSkills(result);
        }
      } catch (errorInstance) {
        if (
          axios.isCancel(errorInstance) ||
          requestGenerationRef.current !== requestGeneration
        ) {
          return;
        }

        setError({
          message: axios.isAxiosError<{ message?: string }>(errorInstance)
            ? (errorInstance.response?.data?.message ?? errorInstance.message)
            : PassiveSkillApiMessages.Load,
        });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchTree();

    return () => controller.abort();
  }, [apiHandler, getUrl]);

  return { passive_skills: passiveSkills, loading, error };
};
