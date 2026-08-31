import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseMonsterForEditDefinition from './definitions/use-monster-for-edit-definition';
import MonsterFormDefinition from '../definitions/monster-form-definition';
import { MonsterApiMessages } from '../enums/monster-api-messages';
import { MonsterApiUrls } from '../enums/monster-api-urls';

export const useMonsterForEdit = (
  monsterId: number | null
): UseMonsterForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [monster, setMonster] = useState<MonsterFormDefinition | null>(null);
  const [loading, setLoading] = useState(monsterId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (monsterId === null) {
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

    const fetchMonster = async () => {
      try {
        const result = await apiHandler.get<
          MonsterFormDefinition,
          Record<string, never>
        >(getUrl(MonsterApiUrls.EDIT, { monster: monsterId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setMonster(result);
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

        setError({ message: MonsterApiMessages.Load });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchMonster();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, monsterId]);

  return { monster, loading, error };
};
