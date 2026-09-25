import ChatType from '../../../../../api-definitions/chat/chat-message-definition';
import ServerMessagesDefinition from '../../../../../api-definitions/chat/server-messages-definition';
import ExplorationMessageRecordDefinition from '../../../components/exploration-messages/definitions/exploration-message-record-definition';

export interface UseChatStreamDefinition {
  server: ServerMessagesDefinition[];
  exploration: ExplorationMessageRecordDefinition[];
  chatMessages: ChatType[];
  prependChatMessage: (message: ChatType) => void;
  replaceChatMessages: (messages: ChatType[]) => void;
  ready: boolean;
}
