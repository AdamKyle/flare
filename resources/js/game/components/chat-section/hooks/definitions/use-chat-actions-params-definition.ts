import ChatType from '../../../../api-definitions/chat/chat-message-definition';
import SendPrivateChatMessageRequest from '../../api/hooks/definitions/send-private-chat-message-request';

export default interface UseChatActionsParamsDefinition {
  chatMessages: ChatType[];
  prependChatMessage: (message: ChatType) => void;
  replaceChatMessages: (messages: ChatType[]) => void;
  setRequestParams: (payload: { message: string }) => void;
  sendPrivateMessage: (
    request: SendPrivateChatMessageRequest
  ) => Promise<boolean>;
}
