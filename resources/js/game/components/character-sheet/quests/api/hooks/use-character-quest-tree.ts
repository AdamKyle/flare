import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseCharacterQuestTreeDefinition from './definitions/use-character-quest-tree-definition';
import UseCharacterQuestTreeParams from './definitions/use-character-quest-tree-params';
import QuestTreeNodeDefinition from '../../../../../reusable-components/quest/api/definitions/quest-tree-node-definition';
import { buildQuestTreeQueryKey } from '../../../../../reusable-components/quest/utils/build-quest-tree-query-key';
import CharacterQuestTreeResponseDefinition from '../definitions/character-quest-tree-response-definition';
import { CharacterQuestApiUrls } from '../enums/character-quest-api-urls';

export const useCharacterQuestTree = ({
  characterId,
  mapId,
  kind,
}: UseCharacterQuestTreeParams): UseCharacterQuestTreeDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [quests, setQuests] = useState<QuestTreeNodeDefinition[]>([]);
  const [completedQuestIds, setCompletedQuestIds] = useState<number[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] =
    useState<UseCharacterQuestTreeDefinition['error']>(null);
  const [queryKey, setQueryKey] = useState<string | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchTree = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();

    const requestQueryKey = buildQuestTreeQueryKey(mapId, kind);

    if (characterId <= 0 || mapId === null) {
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
        CharacterQuestTreeResponseDefinition,
        Record<string, unknown>
      >(getUrl(CharacterQuestApiUrls.TREE, { character: characterId }), {
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
      setCompletedQuestIds(result.completed_quest_ids);
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
  }, [apiHandler, getUrl, characterId, mapId, kind]);

  useEffect(() => {
    void fetchTree();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchTree]);

  const replaceCompletedQuestIds = (ids: number[]): void => {
    setCompletedQuestIds(ids);
  };

  return {
    quests,
    completedQuestIds,
    loading,
    error,
    queryKey,
    refresh: () => void fetchTree(),
    replaceCompletedQuestIds,
  };
};
