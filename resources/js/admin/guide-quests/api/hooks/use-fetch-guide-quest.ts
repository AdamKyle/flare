import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import UseFetchGuideQuestsDefinition from './definitions/use-fetch-guide-quests-definition';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';
import UseFetchGuideQuestParamsDefinition from './definitions/use-fetch-guide-quest-params-definition';
import GuideQuestResponseDefinition from '../definitions/guide-quest-response-defintion';

export const useFetchGuideQuest = ({
  id,
}: UseFetchGuideQuestParamsDefinition): UseFetchGuideQuestsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] = useState<GuideQuestResponseDefinition | null>(null);
  const [error, setError] =
    useState<UseFetchGuideQuestsDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  const url = getUrl(GuideQuestApiUrls.FETCH_GUIDE_QUEST);

  const fetchGuideQuest = useCallback(async () => {
    try {
      const result = await apiHandler.get<
        GuideQuestResponseDefinition,
        AxiosRequestConfig<AxiosResponse<GuideQuestResponseDefinition>>
      >(url, {
        params: {
          guide_quest_id: id,
        },
      });

      console.log(result);

      setData(result);
    } catch (err) {
      if (err instanceof AxiosError) {
        handleInactivity({
          response: err,
          setError,
        });

        setError(err.response?.data || null);
      }
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchGuideQuest().catch(() => {});
  }, [fetchGuideQuest]);

  return {
    data,
    error,
    loading,
  };
};
