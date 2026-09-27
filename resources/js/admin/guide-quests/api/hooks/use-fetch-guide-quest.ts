import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseFetchGuideQuestParamsDefinition from './definitions/use-fetch-guide-quest-params-definition';
import UseFetchGuideQuestsDefinition from './definitions/use-fetch-guide-quests-definition';
import GuideQuestResponseDefinition from '../definitions/guide-quest-response-defintion';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';

export const useFetchGuideQuest = ({
  id,
}: UseFetchGuideQuestParamsDefinition): UseFetchGuideQuestsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const [data, setData] = useState<GuideQuestResponseDefinition | null>(null);
  const [error, setError] =
    useState<UseFetchGuideQuestsDefinition['error']>(null);
  const [loading, setLoading] = useState(true);
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
          GuideQuestResponseDefinition,
          { guide_quest_id: number }
        >(getUrl(GuideQuestApiUrls.FETCH_GUIDE_QUEST), {
          params: { guide_quest_id: id },
          signal: controller.signal,
        });

        if (requestGenerationRef.current === requestGeneration) {
          setData(result);
        }
      } catch (errorInstance) {
        if (
          axios.isCancel(errorInstance) ||
          requestGenerationRef.current !== requestGeneration
        ) {
          return;
        }

        if (axios.isAxiosError(errorInstance)) {
          handleInactivity({ response: errorInstance, setError });
          setError(errorInstance.response?.data ?? null);
        }
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchGuideQuest();
    return () => controller.abort();
  }, [apiHandler, getUrl, handleInactivity, id]);

  const updateGuideQuest = (
    updatedData: GuideQuestResponseDefinition
  ): void => {
    setData((previous) =>
      previous
        ? { ...previous, guide_quest: updatedData.guide_quest }
        : previous
    );
  };

  return { data, error, loading, updateGuideQuest };
};
