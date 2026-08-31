import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseMonsterDetailDefinition from './definitions/use-monster-detail-definition';
import MonsterDetailDefinition from '../../../../game/reusable-components/monster/api/definitions/monster-detail-definition';
import { MonsterApiMessages } from '../enums/monster-api-messages';
import { MonsterApiUrls } from '../enums/monster-api-urls';

export const useMonsterDetail = (
  monsterId: number
): UseMonsterDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [monster, setMonster] = useState<MonsterDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchMonster = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        MonsterDetailDefinition,
        Record<string, never>
      >(getUrl(MonsterApiUrls.SHOW, { monster: monsterId }), {
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
  }, [apiHandler, getUrl, monsterId]);

  useEffect(() => {
    void fetchMonster();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchMonster, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return { monster, loading, error, refresh };
};
