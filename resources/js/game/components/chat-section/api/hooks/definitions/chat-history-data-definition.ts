import AnnouncementMessageDefinition from '../../../../../api-definitions/chat/annoucement-message-definition';
import { ChatMessageType } from '../../../../../api-definitions/chat/chat-message-definition';
import ExplorationMessageDefinition from '../../../../../api-definitions/chat/exploration-message-definition';
import ServerMessagesDefinition from '../../../../../api-definitions/chat/server-messages-definition';

export interface ChatHistoryDataDefinition {
  serverMessages: ServerMessagesDefinition[];
  explorationMessages: ExplorationMessageDefinition[];
  chat_messages: ChatMessageType[];
  announcements: AnnouncementMessageDefinition[];
}
