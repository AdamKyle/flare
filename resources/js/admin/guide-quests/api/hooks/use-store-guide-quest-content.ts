import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useRef, useState } from 'react';

import UseStoreGuideQuestContentDefinition from './definitions/use-store-guide-quest-content-definition';
import GuideQuestResponseDefinition from '../definitions/guide-quest-response-defintion';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';
import UseStoreGuideQuestContentParams from './definitions/use-store-guide-quest-content-params';
import UseStoreGuideQuestRequestDefinition from './definitions/use-store-guide-quest-request-definition';

export const useStoreGuideQuestContent = ({
  update_guide_quest,
}: UseStoreGuideQuestContentParams): UseStoreGuideQuestContentDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [error, setError] =
    useState<UseStoreGuideQuestContentDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const url = getUrl(GuideQuestApiUrls.STORE_COMPONENT_CONTENT);
  const inFlightRef = useRef(false);

  const storeGuideQuestContent = useCallback(
    async (request: UseStoreGuideQuestRequestDefinition): Promise<boolean> => {
      if (inFlightRef.current) {
        return false;
      }

      const formData = new FormData();

      formData.append('guide_quest_id', String(request.guide_quest_id));
      formData.append('has_image', request.has_image ? '1' : '0');

      for (const [key, value] of request.content.entries()) {
        const idx = key.indexOf('[');

        const normalizedKey =
          idx === -1
            ? `content[${key}]`
            : `content[${key.slice(0, idx)}]${key.slice(idx)}`;

        formData.append(normalizedKey, value);
      }

      try {
        inFlightRef.current = true;
        setLoading(true);
        setError(null);

        const result = await apiHandler.post<
          GuideQuestResponseDefinition,
          AxiosRequestConfig<AxiosResponse<GuideQuestResponseDefinition>>,
          FormData
        >(url, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });

        update_guide_quest(result);
        return true;
      } catch (err) {
        if (err instanceof AxiosError) {
          handleInactivity({ response: err, setError });
          setError(err.response?.data || null);
        }

        return false;
      } finally {
        inFlightRef.current = false;
        setLoading(false);
      }
    },
    [apiHandler, handleInactivity, update_guide_quest, url]
  );

  return {
    error,
    loading,
    storeGuideQuestContent,
  };
};
