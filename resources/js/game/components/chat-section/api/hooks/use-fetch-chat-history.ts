import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { UseFetchChatHistoryDefinition } from './definitions/use-fetch-chat-history-definition';
import { UseFetchChatHistoryParams } from './definitions/use-fetch-chat-history-params';
import { ChatApiUrls } from '../enums/chat-api-urls';
import { ChatHistoryDataDefinition } from './definitions/chat-history-data-definition';

export const useFetchChatHistory = (
  params: UseFetchChatHistoryParams
): UseFetchChatHistoryDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<ChatHistoryDataDefinition | null>(null);
  const [error, setError] =
    useState<UseFetchChatHistoryDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  const url = getUrl(ChatApiUrls.FETCH_HISTORY, {
    character: params.character_id,
  });

  const fetchHistory = useCallback(async () => {
    try {
      const result = await apiHandler.get<
        ChatHistoryDataDefinition,
        AxiosRequestConfig<AxiosResponse<ChatHistoryDataDefinition>>
      >(url);

      setData(result);
    } catch (err) {
      if (err instanceof AxiosError) {
        setError(err.response?.data || null);
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, url]);

  useEffect(() => {
    fetchHistory().catch(() => {});
  }, [fetchHistory]);

  return {
    data,
    error,
    loading,
  };
};
