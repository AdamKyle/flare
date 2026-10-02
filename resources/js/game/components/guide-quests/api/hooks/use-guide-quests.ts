import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import GuideQuestMessageResponseDefinition from '../definitions/guide-quest-message-response-definition';
import GuideQuestResponseDefinition from '../definitions/guide-quest-response-definition';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';
import UseGuideQuestsDefinition from './definitions/use-guide-quests-definition';

export const useGuideQuests = (userId: number): UseGuideQuestsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const [data, setData] = useState<GuideQuestResponseDefinition | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(userId > 0);

  const fetchControllerRef = useRef<AbortController | null>(null);
  const handInControllerRef = useRef<AbortController | null>(null);
  const acknowledgeControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeControllers = [
        fetchControllerRef.current,
        handInControllerRef.current,
        acknowledgeControllerRef.current,
      ];

      fetchControllerRef.current = null;
      handInControllerRef.current = null;
      acknowledgeControllerRef.current = null;
      activeControllers.forEach((controller) => controller?.abort());
    };
  }, []);

  const fetchGuideQuests = useCallback(async (): Promise<void> => {
    if (userId <= 0) {
      return;
    }

    fetchControllerRef.current?.abort();
    const controller = new AbortController();
    fetchControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        GuideQuestResponseDefinition,
        AxiosRequestConfig
      >(getUrl(GuideQuestApiUrls.CURRENT, { user: userId }), {
        signal: controller.signal,
      });

      if (fetchControllerRef.current !== controller) {
        return;
      }

      setData(result);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (fetchControllerRef.current !== controller) {
        return;
      }

      setError(
        resolveApiErrorMessage(
          requestError,
          'Unable to load Guide Quests right now.'
        )
      );
    } finally {
      if (fetchControllerRef.current === controller) {
        fetchControllerRef.current = null;
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, userId]);

  useEffect(() => {
    void fetchGuideQuests();
  }, [fetchGuideQuests]);

  const handInGuideQuest = useCallback(
    async (guideQuestId: number): Promise<void> => {
      handInControllerRef.current?.abort();
      const controller = new AbortController();
      handInControllerRef.current = controller;

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
          {},
          { signal: controller.signal }
        );

        if (handInControllerRef.current !== controller) {
          return;
        }

        setData(result);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        if (handInControllerRef.current !== controller) {
          return;
        }

        setError(
          resolveApiErrorMessage(
            requestError,
            'Unable to hand in this Guide Quest.'
          )
        );
      } finally {
        if (handInControllerRef.current === controller) {
          handInControllerRef.current = null;
        }
      }
    },
    [apiHandler, getUrl, userId]
  );

  const acknowledgeAttention = useCallback(async (): Promise<boolean> => {
    if (userId <= 0) {
      return false;
    }

    acknowledgeControllerRef.current?.abort();
    const controller = new AbortController();
    acknowledgeControllerRef.current = controller;

    try {
      await apiHandler.post<
        GuideQuestMessageResponseDefinition,
        AxiosRequestConfig,
        Record<string, never>
      >(
        getUrl(GuideQuestApiUrls.ACKNOWLEDGE_ATTENTION, { user: userId }),
        {},
        { signal: controller.signal }
      );

      if (acknowledgeControllerRef.current !== controller) {
        return false;
      }

      setData((previous) =>
        previous ? { ...previous, has_activity_attention: false } : previous
      );

      return true;
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return false;
      }

      if (acknowledgeControllerRef.current !== controller) {
        return false;
      }

      setError(
        resolveApiErrorMessage(
          requestError,
          'Unable to acknowledge the Guide Quest update.'
        )
      );

      return false;
    } finally {
      if (acknowledgeControllerRef.current === controller) {
        acknowledgeControllerRef.current = null;
      }
    }
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
