import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef } from 'react';

import GuideQuestMessageResponseDefinition from '../definitions/guide-quest-message-response-definition';
import GuideQuestWalkthroughDefinition from '../definitions/guide-quest-walkthrough-definition';
import GuideQuestWalkthroughProgressRequestDefinition from '../definitions/guide-quest-walkthrough-progress-request-definition';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';
import UseGuideQuestWalkthroughApiDefinition from './definitions/use-guide-quest-walkthrough-api-definition';

export const useGuideQuestWalkthroughApi = (
  userId: number
): UseGuideQuestWalkthroughApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchControllerRef = useRef<AbortController | null>(null);
  const saveControllerRef = useRef<AbortController | null>(null);

  const abortWalkthroughRequests = useCallback((): void => {
    const activeFetchController = fetchControllerRef.current;
    const activeSaveController = saveControllerRef.current;

    fetchControllerRef.current = null;
    saveControllerRef.current = null;
    activeFetchController?.abort();
    activeSaveController?.abort();
  }, []);

  useEffect(() => abortWalkthroughRequests, [abortWalkthroughRequests]);

  const fetchWalkthrough = useCallback(
    async (guideQuestId: number): Promise<GuideQuestWalkthroughDefinition> => {
      fetchControllerRef.current?.abort();
      const controller = new AbortController();
      fetchControllerRef.current = controller;

      try {
        return await apiHandler.get<
          GuideQuestWalkthroughDefinition,
          AxiosRequestConfig
        >(
          getUrl(GuideQuestApiUrls.WALKTHROUGH, {
            user: userId,
            guideQuest: guideQuestId,
          }),
          { signal: controller.signal }
        );
      } finally {
        if (fetchControllerRef.current === controller) {
          fetchControllerRef.current = null;
        }
      }
    },
    [apiHandler, getUrl, userId]
  );

  const saveProgress = useCallback(
    async (
      guideQuestId: number,
      stepId: number | null,
      completed: boolean
    ): Promise<void> => {
      saveControllerRef.current?.abort();
      const controller = new AbortController();
      saveControllerRef.current = controller;

      try {
        await apiHandler.put<
          GuideQuestMessageResponseDefinition,
          Record<string, never>,
          GuideQuestWalkthroughProgressRequestDefinition
        >(
          getUrl(GuideQuestApiUrls.WALKTHROUGH, {
            user: userId,
            guideQuest: guideQuestId,
          }),
          { step_id: stepId, completed },
          { signal: controller.signal }
        );
      } finally {
        if (saveControllerRef.current === controller) {
          saveControllerRef.current = null;
        }
      }
    },
    [apiHandler, getUrl, userId]
  );

  return { fetchWalkthrough, saveProgress, abortWalkthroughRequests };
};
