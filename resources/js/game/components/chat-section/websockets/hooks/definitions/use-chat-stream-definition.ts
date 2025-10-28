import AnnouncementMessageDefinition from '../../../../../api-definitions/chat/annoucement-message-definition';
import ChatType from '../../../../../api-definitions/chat/chat-message-definition';
import ExplorationMessageDefinition from '../../../../../api-definitions/chat/exploration-message-definition';
import ServerMessagesDefinition from '../../../../../api-definitions/chat/server-messages-definition';

export interface UseChatStreamDefinition {
  server: ServerMessagesDefinition[];
  exploration: ExplorationMessageDefinition[];
  announcements: AnnouncementMessageDefinition[];
  chatMessages: ChatType[];
  ready: boolean;
}
