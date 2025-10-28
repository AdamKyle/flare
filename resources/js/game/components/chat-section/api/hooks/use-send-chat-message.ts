import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import SendChatMessageRequest from './definitions/send-chat-message-request';
import UseSendChatMessageDefinition from './definitions/use-send-chat-message-definition';
import UseSendChatMessageParams from './definitions/use-send-chat-message-params';
import { ChatApiUrls } from '../enums/chat-api-urls';
import SendChatMessageResponse from './definitions/send-chat-message-response';

export const useSendChatMessage = (
  params: UseSendChatMessageParams
): UseSendChatMessageDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [loading, setLoading] = useState(false);
  const [error, setError] =
    useState<UseSendChatMessageDefinition['error']>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [requestParams, setRequestParams] = useState<SendChatMessageRequest>({
    message: '',
  });

  const url = getUrl(ChatApiUrls.SEND_MESSAGE, {
    character: params.character_id,
  });

  const send = useCallback(
    async () => {
      if (requestParams.message === '') {
        return;
      }

      setLoading(true);
      setError(null);
      setSuccessMessage(null);

      try {
        const result = await apiHandler.post<
          SendChatMessageResponse,
          AxiosRequestConfig<AxiosResponse<SendChatMessageResponse>>,
          SendChatMessageRequest
        >(url, {
          message: requestParams.message,
        });

        setSuccessMessage(result.message);

        if (params.on_success) {
          params.on_success();
        }
      } catch (err) {
        if (err instanceof AxiosError) {
          setError(err.response?.data || null);
        }
      } finally {
        setLoading(false);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [apiHandler, url]
  );

  useEffect(() => {
    send().catch(() => {});
  }, [send]);

  return {
    loading,
    error,
    successMessage,
    setRequestParams,
  };
};
