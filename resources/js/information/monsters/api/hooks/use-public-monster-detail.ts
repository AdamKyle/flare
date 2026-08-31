import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { MonsterInfoApiUrls } from '../enums/monster-info-api-urls';
import UsePublicMonsterDetailDefinition from './definitions/use-public-monster-detail-definition';
import MonsterDetailDefinition from '../../../../game/reusable-components/monster/api/definitions/monster-detail-definition';

/**
 * Public, read-only Monster detail hook. Calls the public Information API,
 * never an Admin endpoint, and never sends Admin credentials or permission
 * state.
 *
 * @param monsterId Monster id to load.
 */
export const usePublicMonsterDetail = (
  monsterId: number
): UsePublicMonsterDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [monster, setMonster] = useState<MonsterDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] =
    useState<UsePublicMonsterDetailDefinition['error']>(null);

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
      >(getUrl(MonsterInfoApiUrls.SHOW, { monster: monsterId }), {
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

      setError({ message: 'Unable to load this Monster.' });
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
  }, [fetchMonster]);

  return { monster, loading, error };
};
