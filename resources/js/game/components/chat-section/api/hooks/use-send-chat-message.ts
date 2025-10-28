import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import SendChatMessageRequest from './definitions/send-chat-message-request';
import UseSendChatMessageDefinition from './definitions/use-send-chat-message-definition';
import { ChatApiUrls } from '../enums/chat-api-urls';
import SendChatMessageResponse from './definitions/send-chat-message-response';

export const useSendChatMessage = (): UseSendChatMessageDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [error, setError] =
    useState<UseSendChatMessageDefinition['error']>(null);

  const [requestParams, setRequestParams] = useState<SendChatMessageRequest>({
    message: '',
  });

  const url = getUrl(ChatApiUrls.SEND_MESSAGE);

  const send = useCallback(async () => {
    setError(null);

    console.log('requestParams', requestParams);

    try {
      const result = await apiHandler.post<
        SendChatMessageResponse,
        AxiosRequestConfig<AxiosResponse<SendChatMessageResponse>>,
        SendChatMessageRequest
      >(url, {
        message: requestParams.message,
      });

      console.log('result', result);
    } catch (err) {
      if (err instanceof AxiosError) {
        setError(err.response?.data || null);
      }
    }
  }, [apiHandler, url, requestParams]);

  useEffect(() => {
    if (requestParams.message === '') {
      return;
    }
    console.log('useEffect - requestParams', requestParams);
    send().catch(() => {});
  }, [send, requestParams]);

  return {
    error,
    setRequestParams,
  };
};
