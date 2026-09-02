import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseQuestTreeDefinition from './definitions/use-quest-tree-definition';
import QuestTreeNodeDefinition from '../../../../game/reusable-components/quest/api/definitions/quest-tree-node-definition';
import { QuestKind } from '../../../../game/reusable-components/quest/enums/quest-kind';
import QuestTreeResponseDefinition from '../definitions/quest-tree-response-definition';
import { QuestApiMessages } from '../enums/quest-api-messages';
import { QuestApiUrls } from '../enums/quest-api-urls';

export const useQuestTree = (
  mapId: number | null,
  kind: QuestKind | null
): UseQuestTreeDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [quests, setQuests] = useState<QuestTreeNodeDefinition[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<UseQuestTreeDefinition['error']>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchTree = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();

    if (mapId === null) {
      abortControllerRef.current = null;
      setQuests([]);
      setError(null);
      setLoading(false);

      return;
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setQuests([]);
    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        QuestTreeResponseDefinition,
        Record<string, unknown>
      >(getUrl(QuestApiUrls.TREE), {
        signal: controller.signal,
        params: {
          map_id: mapId ?? undefined,
          kind: kind ?? undefined,
        },
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setQuests(result.quests);
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

      setError({ message: QuestApiMessages.LoadTree });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, mapId, kind]);

  useEffect(() => {
    void fetchTree();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchTree, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return { quests, loading, error, refresh };
};
