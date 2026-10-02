import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosRequestConfig } from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseStoreGuideQuestWalkthroughDefinition from './definitions/use-store-guide-quest-walkthrough-definition';
import { GuideQuestWalkthroughStepDefinition } from '../definitions/guide-quest-definition';
import StoreGuideQuestWalkthroughRequestDefinition from '../definitions/store-guide-quest-walkthrough-request-definition';
import StoreGuideQuestWalkthroughResponseDefinition from '../definitions/store-guide-quest-walkthrough-response-definition';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';

export const useStoreGuideQuestWalkthrough =
  (): UseStoreGuideQuestWalkthroughDefinition => {
    const { apiHandler, getUrl } = useApiHandler();
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const isMountedRef = useRef(true);
    const savingRef = useRef(false);
    const abortControllerRef = useRef<AbortController | null>(null);

    useEffect(() => {
      isMountedRef.current = true;

      return () => {
        isMountedRef.current = false;
        abortControllerRef.current?.abort();
      };
    }, []);

    const storeWalkthrough = async (
      guideQuestId: number,
      steps: GuideQuestWalkthroughStepDefinition[]
    ): Promise<boolean> => {
      if (savingRef.current) {
        return false;
      }

      savingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setLoading(true);
      setError(null);

      try {
        await apiHandler.put<
          StoreGuideQuestWalkthroughResponseDefinition,
          AxiosRequestConfig,
          StoreGuideQuestWalkthroughRequestDefinition
        >(
          getUrl(GuideQuestApiUrls.STORE_WALKTHROUGH, {
            guideQuest: guideQuestId,
          }),
          { steps },
          { signal: controller.signal }
        );

        return true;
      } catch (requestError) {
        if (axios.isCancel(requestError) || !isMountedRef.current) {
          return false;
        }

        setError(
          resolveApiErrorMessage(
            requestError,
            'Unable to save the guided walkthrough.'
          )
        );

        return false;
      } finally {
        savingRef.current = false;
        abortControllerRef.current = null;

        if (isMountedRef.current) {
          setLoading(false);
        }
      }
    };

    return { error, loading, storeWalkthrough };
  };
