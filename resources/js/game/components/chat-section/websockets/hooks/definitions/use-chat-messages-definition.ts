import ChatType from '../../../../../api-definitions/chat/chat-message-definition';

export interface UseChatMessagesDefinition {
  chatMessages: ChatType[];
  prependChatMessage: (message: ChatType) => void;
  replaceChatMessages: (messages: ChatType[]) => void;
}
