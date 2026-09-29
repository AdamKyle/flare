import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useState } from 'react';

import { GuideQuestWalkthroughStepDefinition } from '../definitions/guide-quest-definition';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';

export const useStoreGuideQuestWalkthrough = () => {
  const { apiHandler, getUrl } = useApiHandler();
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const storeWalkthrough = async (
    guideQuestId: number,
    steps: GuideQuestWalkthroughStepDefinition[]
  ): Promise<boolean> => {
    setLoading(true);
    setError(null);

    try {
      await apiHandler.put<
        { message: string },
        AxiosRequestConfig,
        { steps: GuideQuestWalkthroughStepDefinition[] }
      >(
        getUrl(GuideQuestApiUrls.STORE_WALKTHROUGH, {
          guideQuest: guideQuestId,
        }),
        { steps }
      );

      return true;
    } catch (requestError) {
      if (requestError instanceof AxiosError) {
        setError(
          requestError.response?.data?.message ??
            'Unable to save the guided walkthrough.'
        );
      }

      return false;
    } finally {
      setLoading(false);
    }
  };

  return { error, loading, storeWalkthrough };
};
