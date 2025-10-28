import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SendChatMessageRequest from './send-chat-message-request';
import { StateSetter } from '../../../../../../types/state-setter-type';

export default interface UseSendChatMessageDefinition {
  error: AxiosErrorDefinition | null;
  setRequestParams: StateSetter<SendChatMessageRequest>;
}
