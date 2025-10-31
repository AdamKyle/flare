import AnnouncementMessageDefinition from '../../../../api-definitions/chat/annoucement-message-definition';
import ChatType from '../../../../api-definitions/chat/chat-message-definition';

export default interface UseChatActionsDefinition {
  combinedChat: {
    chat: ChatType[];
    announcements: AnnouncementMessageDefinition[] | undefined;
  };
  setInitialAnnouncements: (data: AnnouncementMessageDefinition[]) => void;
  setInitialChatHistory: (history: ChatType[]) => void;
  pushSilencedMessage: () => void;
  pushPrivateMessageSent: (messageData: string[]) => void;
  pushErrorMessage: (message: string) => void;
  onSend: (text: string) => void;
}
