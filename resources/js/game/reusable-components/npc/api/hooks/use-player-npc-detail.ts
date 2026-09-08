import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UsePlayerNpcDetailDefinition from './definitions/use-player-npc-detail-definition';
import NpcFactualDefinition from '../../types/npc-factual-definition';
import { PlayerNpcDetailApiUrls } from '../enums/player-npc-detail-api-urls';

export const usePlayerNpcDetail = (
  npcId: number
): UsePlayerNpcDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [npc, setNpc] = useState<NpcFactualDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchNpc = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        NpcFactualDefinition,
        Record<string, never>
      >(getUrl(PlayerNpcDetailApiUrls.DETAIL, { npc: npcId }), {
        signal: controller.signal,
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setNpc(result);
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

      setError({ message: 'Unable to load this NPC.' });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, npcId]);

  useEffect(() => {
    void fetchNpc();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchNpc, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return {
    npc,
    loading,
    error,
    refresh,
  };
};
