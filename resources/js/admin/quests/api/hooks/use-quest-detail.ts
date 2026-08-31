import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseQuestDetailDefinition from './definitions/use-quest-detail-definition';
import QuestDetailDefinition from '../../../../game/reusable-components/quest/api/definitions/quest-detail-definition';
import { QuestApiMessages } from '../enums/quest-api-messages';
import { QuestApiUrls } from '../enums/quest-api-urls';

export const useQuestDetail = (questId: number): UseQuestDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [quest, setQuest] = useState<QuestDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<UseQuestDetailDefinition['error']>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchQuest = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        QuestDetailDefinition,
        Record<string, never>
      >(getUrl(QuestApiUrls.SHOW, { quest: questId }), {
        signal: controller.signal,
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setQuest(result);
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

      setError({ message: QuestApiMessages.Load });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, questId]);

  useEffect(() => {
    void fetchQuest();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchQuest, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return { quest, loading, error, refresh };
};
