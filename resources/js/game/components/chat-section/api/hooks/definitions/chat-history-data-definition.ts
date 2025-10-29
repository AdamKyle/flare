import { PublicChatHistoryDefinition } from './public-chat-history-definition';
import AnnouncementMessageDefinition from '../../../../../api-definitions/chat/annoucement-message-definition';

export interface ChatHistoryDataDefinition {
  chat_messages: PublicChatHistoryDefinition[];
  announcements: AnnouncementMessageDefinition[];
}
