import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, isCancel } from 'axios';
import { useEffect, useState } from 'react';

import UseFetchAdminChatHistoryDefinition from './definitions/use-fetch-admin-chat-history-definition';
import AdminChatHistoryResponseDefinition from '../definitions/admin-chat-history-response-definition';
import { AdminChatApiUrls } from '../enums/admin-chat-api-urls';

export const useFetchAdminChatHistory =
  (): UseFetchAdminChatHistoryDefinition => {
    const { apiHandler, getUrl } = useApiHandler();
    const { handleInactivity } = useActivityTimeout();

    const [data, setData] = useState<AdminChatHistoryResponseDefinition | null>(
      null
    );
    const [error, setError] = useState<AxiosErrorDefinition | null>(null);
    const [loading, setLoading] = useState(true);

    const url = getUrl(AdminChatApiUrls.FETCH_HISTORY);

    useEffect(() => {
      const abortController = new AbortController();

      const fetchHistory = async (): Promise<void> => {
        try {
          const result = await apiHandler.get<
            AdminChatHistoryResponseDefinition,
            never
          >(url, {
            signal: abortController.signal,
          });

          setData(result);
          setLoading(false);
        } catch (requestError) {
          if (isCancel(requestError)) {
            return;
          }

          setError({ message: 'Unable to load the chat history.' });
          setLoading(false);

          if (requestError instanceof AxiosError) {
            handleInactivity({ setError, response: requestError });
          }
        }
      };

      fetchHistory().catch(() => {
        setError({ message: 'Unable to load the chat history.' });
        setLoading(false);
      });

      return () => {
        abortController.abort();
      };
    }, [apiHandler, handleInactivity, url]);

    return {
      data,
      error,
      loading,
    };
  };
