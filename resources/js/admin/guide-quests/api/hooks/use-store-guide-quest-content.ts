import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseStoreGuideQuestContentDefinition from './definitions/use-store-guide-quest-content-definition';
import GuideQuestResponseDefinition from '../definitions/guide-quest-response-defintion';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';
import UseStoreGuideQuestContentParams from './definitions/use-store-guide-quest-content-params';

export const useStoreGuideQuestContent = ({
  update_guide_quest,
}: UseStoreGuideQuestContentParams): UseStoreGuideQuestContentDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [error, setError] =
    useState<UseStoreGuideQuestContentDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [canMoveForward, setCanMoveForward] = useState(false);
  const [requestParams, setRequestParams] = useState({
    guide_quest_id: 0,
    has_image: false,
    content: new FormData(),
  });

  const url = getUrl(GuideQuestApiUrls.STORE_COMPONENT_CONTENT);

  const inFlightRef = useRef(false);
  const lastSignatureRef = useRef<string>('');

  const resetFormData = () => {
    setRequestParams({
      guide_quest_id: 0,
      has_image: false,
      content: new FormData(),
    });
  };

  const buildSignature = (params: typeof requestParams): string => {
    const parts: string[] = [];

    parts.push(String(params.guide_quest_id));
    parts.push(params.has_image ? '1' : '0');

    for (const [key, value] of params.content.entries()) {
      if (value instanceof File) {
        parts.push(`${key}:file:${value.name}:${value.size}:${value.type}`);
      } else {
        parts.push(`${key}:text:${String(value)}`);
      }
    }

    return parts.join('|');
  };

  const postGuideQuestContent = useCallback(async () => {
    if (requestParams.content.keys().next().done) {
      return;
    }

    const signature = buildSignature(requestParams);

    if (inFlightRef.current) {
      return;
    }
    if (signature === lastSignatureRef.current) {
      return;
    }

    const formData = new FormData();

    formData.append('guide_quest_id', String(requestParams.guide_quest_id));
    formData.append('has_image', requestParams.has_image ? '1' : '0');

    for (const [key, value] of requestParams.content.entries()) {
      const idx = key.indexOf('[');

      const normalizedKey =
        idx === -1
          ? `content[${key}]`
          : `content[${key.slice(0, idx)}]${key.slice(idx)}`;

      formData.append(normalizedKey, value);
    }

    if (formData.keys().next().done) {
      return;
    }

    try {
      inFlightRef.current = true;
      setLoading(true);
      setError(null);

      const result = await apiHandler.post<
        GuideQuestResponseDefinition,
        AxiosRequestConfig<AxiosResponse<GuideQuestResponseDefinition>>,
        FormData
      >(url, formData, { headers: { 'Content-Type': 'multipart/form-data' } });

      lastSignatureRef.current = signature;

      update_guide_quest(result);

      setCanMoveForward(true);
    } catch (err) {
      if (err instanceof AxiosError) {
        handleInactivity({ response: err, setError });
        setError(err.response?.data || null);
      }
    } finally {
      inFlightRef.current = false;

      setLoading(false);
      resetFormData();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [apiHandler, handleInactivity, requestParams, update_guide_quest, url]);

  useEffect(() => {
    postGuideQuestContent().catch(() => {});
  }, [postGuideQuestContent]);

  return {
    error,
    loading,
    canMoveForward,
    setRequestParams,
  };
};
