import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseQuestForEditDefinition from './definitions/use-quest-for-edit-definition';
import QuestFormDefinition from '../definitions/quest-form-definition';
import { QuestApiMessages } from '../enums/quest-api-messages';
import { QuestApiUrls } from '../enums/quest-api-urls';

export const useQuestForEdit = (
  questId: number | null
): UseQuestForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [quest, setQuest] = useState<QuestFormDefinition | null>(null);
  const [loading, setLoading] = useState(questId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (questId === null) {
      setLoading(false);

      return;
    }

    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchQuest = async () => {
      try {
        const result = await apiHandler.get<
          QuestFormDefinition,
          Record<string, never>
        >(getUrl(QuestApiUrls.EDIT, { quest: questId }), {
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
    };

    void fetchQuest();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, questId]);

  return { quest, loading, error };
};
