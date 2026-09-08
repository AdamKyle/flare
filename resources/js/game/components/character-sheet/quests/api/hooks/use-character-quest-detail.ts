import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseCharacterQuestDetailDefinition from './definitions/use-character-quest-detail-definition';
import UseCharacterQuestDetailParams from './definitions/use-character-quest-detail-params';
import QuestDetailDefinition from '../../../../../reusable-components/quest/api/definitions/quest-detail-definition';
import QuestItemOwnershipState from '../../../../side-peeks/components/items/enums/quest-item-ownership-state';
import CharacterQuestDetailResponseDefinition from '../definitions/character-quest-detail-response-definition';
import CharacterQuestReadinessDefinition from '../definitions/character-quest-readiness-definition';
import { CharacterQuestApiUrls } from '../enums/character-quest-api-urls';

export const useCharacterQuestDetail = ({
  characterId,
  questId,
}: UseCharacterQuestDetailParams): UseCharacterQuestDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [quest, setQuest] = useState<QuestDetailDefinition | null>(null);
  const [completedQuestIds, setCompletedQuestIds] = useState<number[]>([]);
  const [readiness, setReadiness] =
    useState<CharacterQuestReadinessDefinition | null>(null);
  const [questItemOwnership, setQuestItemOwnership] = useState<
    Record<number, QuestItemOwnershipState>
  >({});
  const [loading, setLoading] = useState(true);
  const [error, setError] =
    useState<UseCharacterQuestDetailDefinition['error']>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchDetail = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();

    if (characterId <= 0 || questId <= 0) {
      abortControllerRef.current = null;
      setLoading(false);
      setError(null);

      return;
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        CharacterQuestDetailResponseDefinition,
        Record<string, never>
      >(
        getUrl(CharacterQuestApiUrls.DETAIL, {
          character: characterId,
          quest: questId,
        }),
        { signal: controller.signal }
      );

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setQuest(result.quest);
      setCompletedQuestIds(result.completed_quest_ids);
      setReadiness(result.readiness);
      setQuestItemOwnership(result.quest_item_ownership);
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

      setError({ message: 'Unable to load this Quest.' });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, characterId, questId]);

  useEffect(() => {
    void fetchDetail();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchDetail]);

  return {
    quest,
    completedQuestIds,
    readiness,
    questItemOwnership,
    loading,
    error,
    refresh: () => void fetchDetail(),
  };
};
