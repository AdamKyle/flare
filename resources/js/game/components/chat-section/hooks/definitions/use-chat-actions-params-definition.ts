import ChatType from '../../../../api-definitions/chat/chat-message-definition';
import SendPrivateChatMessageRequest from '../../api/hooks/definitions/send-private-chat-message-request';
import SendPublicEntityCommandRequest from '../../api/hooks/definitions/send-public-entity-command-request';

export default interface UseChatActionsParamsDefinition {
  chatMessages: ChatType[];
  prependChatMessage: (message: ChatType) => void;
  replaceChatMessages: (messages: ChatType[]) => void;
  setRequestParams: (payload: { message: string }) => void;
  sendPrivateMessage: (
    request: SendPrivateChatMessageRequest
  ) => Promise<boolean>;
  sendPublicEntityCommand: (
    request: SendPublicEntityCommandRequest
  ) => Promise<boolean>;
}
