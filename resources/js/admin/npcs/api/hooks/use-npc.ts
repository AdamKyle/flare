import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import UseNpcDefinition from './definitions/use-npc-definition';
import NpcDefinition from '../definitions/npc-definition';
import { NpcApiMessages } from '../enums/npc-api-messages';
import { NpcApiUrls } from '../enums/npc-api-urls';

export const useNpc = (
  gameMapId: number,
  npcId: number | null
): UseNpcDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [npc, setNpc] = useState<NpcDefinition | null>(null);
  const [loading, setLoading] = useState(npcId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (npcId === null) {
      setNpc(null);
      setLoading(false);
      setError(null);

      return;
    }

    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchNpc = async () => {
      try {
        const result = await apiHandler.get<
          NpcDefinition,
          Record<string, never>
        >(getUrl(NpcApiUrls.SHOW, { gameMap: gameMapId, npc: npcId }), {
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
    };

    void fetchNpc();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, gameMapId, npcId]);

  return { npc, loading, error };
};
