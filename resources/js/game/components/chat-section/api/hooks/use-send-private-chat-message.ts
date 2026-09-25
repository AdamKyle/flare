import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useState } from 'react';

import SendPrivateChatMessageRequest from './definitions/send-private-chat-message-request';
import SendPrivateChatMessageResponse from './definitions/send-private-chat-message-response';
import UseSendPrivateChatMessageDefinition from './definitions/use-send-private-chat-message-definition';
import { ChatApiUrls } from '../enums/chat-api-urls';

export const useSendPrivateChatMessage =
  (): UseSendPrivateChatMessageDefinition => {
    const { apiHandler, getUrl } = useApiHandler();
    const { handleInactivity } = useActivityTimeout();

    const [error, setError] = useState<AxiosErrorDefinition | null>(null);
    const [loading, setLoading] = useState(false);

    const url = getUrl(ChatApiUrls.SEND_PRIVATE_MESSAGE);

    const sendPrivateMessage = useCallback(
      async (request: SendPrivateChatMessageRequest): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
          const response = await apiHandler.post<
            SendPrivateChatMessageResponse,
            never,
            SendPrivateChatMessageRequest
          >(url, request);

          return response.delivered;
        } catch (requestError) {
          if (!(requestError instanceof AxiosError)) {
            setError({ message: 'Unable to send the private message.' });

            return false;
          }

          setError(
            requestError.response?.data ?? {
              message: 'Unable to send the private message.',
            }
          );

          handleInactivity({ setError, response: requestError });

          return false;
        } finally {
          setLoading(false);
        }
      },
      [apiHandler, handleInactivity, url]
    );

    return {
      error,
      loading,
      sendPrivateMessage,
    };
  };
