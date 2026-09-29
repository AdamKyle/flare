import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosRequestConfig } from 'axios';
import { useCallback } from 'react';

import GuideQuestWalkthroughDefinition from '../definitions/guide-quest-walkthrough-definition';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';

export const useGuideQuestWalkthroughApi = (userId: number) => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchWalkthrough = useCallback(
    async (guideQuestId: number): Promise<GuideQuestWalkthroughDefinition> => {
      return apiHandler.get<
        GuideQuestWalkthroughDefinition,
        AxiosRequestConfig
      >(
        getUrl(GuideQuestApiUrls.WALKTHROUGH, {
          user: userId,
          guideQuest: guideQuestId,
        })
      );
    },
    [apiHandler, getUrl, userId]
  );

  const saveProgress = useCallback(
    async (
      guideQuestId: number,
      stepId: number | null,
      completed: boolean
    ): Promise<void> => {
      await apiHandler.put<
        { message: string },
        Record<string, never>,
        { step_id: number | null; completed: boolean }
      >(
        getUrl(GuideQuestApiUrls.WALKTHROUGH, {
          user: userId,
          guideQuest: guideQuestId,
        }),
        { step_id: stepId, completed }
      );
    },
    [apiHandler, getUrl, userId]
  );

  return { fetchWalkthrough, saveProgress };
};
