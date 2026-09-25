import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SendPrivateChatMessageRequest from './send-private-chat-message-request';

export default interface UseSendPrivateChatMessageDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  sendPrivateMessage: (
    request: SendPrivateChatMessageRequest
  ) => Promise<boolean>;
}
