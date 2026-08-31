import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { QuestInfoApiUrls } from '../enums/quest-info-api-urls';
import UsePublicQuestDetailDefinition from './definitions/use-public-quest-detail-definition';
import QuestDetailDefinition from '../../../../game/reusable-components/quest/api/definitions/quest-detail-definition';

/**
 * Public, read-only Quest detail hook. Calls the public Information API,
 * never an Admin endpoint, and never sends Admin credentials or permission
 * state.
 *
 * @param questId Quest id to load.
 */
export const usePublicQuestDetail = (
  questId: number
): UsePublicQuestDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [quest, setQuest] = useState<QuestDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] =
    useState<UsePublicQuestDetailDefinition['error']>(null);

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
      >(getUrl(QuestInfoApiUrls.SHOW, { quest: questId }), {
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

      setError({ message: 'Unable to load this Quest.' });
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
  }, [fetchQuest]);

  return { quest, loading, error };
};
