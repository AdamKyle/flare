import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UsePublicQuestTreeDefinition from './definitions/use-public-quest-tree-definition';
import QuestTreeNodeDefinition from '../../../../game/reusable-components/quest/api/definitions/quest-tree-node-definition';
import { QuestKind } from '../../../../game/reusable-components/quest/enums/quest-kind';
import { buildQuestTreeQueryKey } from '../../../../game/reusable-components/quest/utils/build-quest-tree-query-key';
import QuestInfoTreeResponseDefinition from '../definitions/quest-info-tree-response-definition';
import { QuestInfoApiUrls } from '../enums/quest-info-api-urls';

/**
 * Public, read-only Quest tree hook. Calls the public Information API, never
 * an Admin endpoint, and never sends Admin credentials or permission state.
 *
 * @param mapId Game Map id to filter root Quests by, when given.
 * @param kind Quest kind to filter root Quests by, when given.
 */
export const usePublicQuestTree = (
  mapId: number | null,
  kind: QuestKind | null
): UsePublicQuestTreeDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [quests, setQuests] = useState<QuestTreeNodeDefinition[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] =
    useState<UsePublicQuestTreeDefinition['error']>(null);
  const [queryKey, setQueryKey] = useState<string | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchTree = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();

    const requestQueryKey = buildQuestTreeQueryKey(mapId, kind);

    if (mapId === null) {
      abortControllerRef.current = null;
      setQuests([]);
      setError(null);
      setLoading(false);
      setQueryKey(null);

      return;
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setQuests([]);
    setLoading(true);
    setError(null);
    setQueryKey(null);

    try {
      const result = await apiHandler.get<
        QuestInfoTreeResponseDefinition,
        Record<string, unknown>
      >(getUrl(QuestInfoApiUrls.TREE), {
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
      setQueryKey(requestQueryKey);
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
        setQueryKey(requestQueryKey);

        return;
      }

      setError({ message: 'Unable to load the Quest tree.' });
      setQueryKey(requestQueryKey);
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
  }, [fetchTree]);

  return { quests, loading, error, query_key: queryKey };
};
