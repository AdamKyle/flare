import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseGuideQuestDetailDefinition from './definitions/use-guide-quest-detail-definition';
import GuideQuestDefinition from '../definitions/guide-quest-definition';
import { GuideQuestApiMessages } from '../enums/guide-quest-api-messages';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';

export const useGuideQuestDetail = (
  guideQuestId: number
): UseGuideQuestDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [guideQuest, setGuideQuest] = useState<GuideQuestDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const requestGenerationRef = useRef(0);

  useEffect(() => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;
    const controller = new AbortController();

    const fetchGuideQuest = async (): Promise<void> => {
      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.get<
          GuideQuestDefinition,
          Record<string, never>
        >(getUrl(GuideQuestApiUrls.SHOW, { guideQuest: guideQuestId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current === requestGeneration) {
          setGuideQuest(result);
        }
      } catch (errorInstance) {
        if (
          axios.isCancel(errorInstance) ||
          requestGenerationRef.current !== requestGeneration
        ) {
          return;
        }

        setError({
          message: axios.isAxiosError<{ message?: string }>(errorInstance)
            ? (errorInstance.response?.data?.message ?? errorInstance.message)
            : GuideQuestApiMessages.LOAD_DETAIL,
        });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchGuideQuest();
    return () => controller.abort();
  }, [apiHandler, getUrl, guideQuestId]);

  return { guide_quest: guideQuest, loading, error };
};
