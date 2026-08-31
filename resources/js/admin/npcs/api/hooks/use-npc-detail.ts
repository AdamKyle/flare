import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseNpcDetailDefinition from './definitions/use-npc-detail-definition';
import NpcDetailDefinition from '../definitions/npc-detail-definition';
import { NpcApiMessages } from '../enums/npc-api-messages';
import { NpcApiUrls } from '../enums/npc-api-urls';

export const useNpcDetail = (npcId: number): UseNpcDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [npc, setNpc] = useState<NpcDetailDefinition | null>(null);
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
        NpcDetailDefinition,
        Record<string, never>
      >(getUrl(NpcApiUrls.DETAIL, { npc: npcId }), {
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

      setError({ message: NpcApiMessages.Load });
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
