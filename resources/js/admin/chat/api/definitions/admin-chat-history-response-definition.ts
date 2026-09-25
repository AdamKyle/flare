import { PublicChatHistoryDefinition } from '../../../../game/components/chat-section/api/hooks/definitions/public-chat-history-definition';

export default interface AdminChatHistoryResponseDefinition {
  chat_messages: PublicChatHistoryDefinition[];
}
