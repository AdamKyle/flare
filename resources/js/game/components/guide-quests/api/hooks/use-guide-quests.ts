import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import GuideQuestResponseDefinition from '../definitions/guide-quest-response-definition';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';

export const useGuideQuests = (userId: number) => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<GuideQuestResponseDefinition | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(userId > 0);

  const fetchGuideQuests = useCallback(async (): Promise<void> => {
    if (userId <= 0) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        GuideQuestResponseDefinition,
        AxiosRequestConfig
      >(getUrl(GuideQuestApiUrls.CURRENT, { user: userId }));

      setData(result);
    } catch (requestError) {
      if (requestError instanceof AxiosError) {
        setError(
          requestError.response?.data?.message ??
            'Unable to load Guide Quests right now.'
        );
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, getUrl, userId]);

  useEffect(() => {
    void fetchGuideQuests();
  }, [fetchGuideQuests]);

  const handInGuideQuest = useCallback(
    async (guideQuestId: number): Promise<void> => {
      setError(null);

      try {
        const result = await apiHandler.post<
          GuideQuestResponseDefinition,
          AxiosRequestConfig,
          Record<string, never>
        >(
          getUrl(GuideQuestApiUrls.HAND_IN, {
            user: userId,
            guideQuest: guideQuestId,
          }),
          {}
        );

        setData(result);
      } catch (requestError) {
        if (requestError instanceof AxiosError) {
          setError(
            requestError.response?.data?.message ??
              'Unable to hand in this Guide Quest.'
          );
        }
      }
    },
    [apiHandler, getUrl, userId]
  );

  const acknowledgeAttention = useCallback(async (): Promise<void> => {
    if (userId <= 0) {
      return;
    }

    await apiHandler.post<
      { message: string },
      AxiosRequestConfig,
      Record<string, never>
    >(getUrl(GuideQuestApiUrls.ACKNOWLEDGE_ATTENTION, { user: userId }), {});

    setData((previous) =>
      previous ? { ...previous, has_activity_attention: false } : previous
    );
  }, [apiHandler, getUrl, userId]);

  return {
    data,
    error,
    loading,
    fetchGuideQuests,
    handInGuideQuest,
    acknowledgeAttention,
  };
};
