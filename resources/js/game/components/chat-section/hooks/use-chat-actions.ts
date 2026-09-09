import { useCallback, useMemo, useState } from 'react';

import UseChatActionsDefinition from './definitions/use-chat-actions-definition';
import UseChatActionsParamsDefinition from './definitions/use-chat-actions-params-definition';
import AnnouncementMessageDefinition from '../../../api-definitions/chat/annoucement-message-definition';
import ChatType, {
  ChatMessageType,
} from '../../../api-definitions/chat/chat-message-definition';

const useChatActions = (
  params: UseChatActionsParamsDefinition
): UseChatActionsDefinition => {
  const { chatMessages, setRequestParams } = params;

  const [localChats, setLocalChats] = useState<ChatType[]>([]);
  const [initialAnnouncements, setInitialAnnouncements] = useState<
    AnnouncementMessageDefinition[]
  >([]);

  const setInitialChatHistory = useCallback((history: ChatType[]): void => {
    setLocalChats(history);
  }, []);

  const buildLocalSystemChat = (
    message: string,
    type: ChatMessageType
  ): ChatType => ({
    color: '',
    map_name: '',
    character_name: '',
    message,
    x: 0,
    y: 0,
    type,
    hide_location: true,
    user_id: 0,
    custom_class: '',
    is_chat_bold: false,
    is_chat_italic: false,
    name_tag: '',
  });

  const pushSilencedMessage = useCallback((): void => {
    setLocalChats((previous) => {
      const next = buildLocalSystemChat(
        "You child, have been chatting up a storm. Slow down. I'll let you know whe you can talk again ...",
        'error-message'
      );

      const updated = [next, ...previous];

      if (updated.length > 1000) {
        return updated.slice(0, 500);
      }

      return updated;
    });
  }, []);

  const pushPrivateMessageSent = useCallback((messageData: string[]): void => {
    setLocalChats((previous) => {
      const next = buildLocalSystemChat(
        `Sent to ${messageData[1]}: ${messageData[2]}`,
        'private-message-sent'
      );

      const updated = [next, ...previous];

      if (updated.length > 1000) {
        return updated.slice(0, 500);
      }

      return updated;
    });
  }, []);

  const pushErrorMessage = useCallback((message: string): void => {
    setLocalChats((previous) => {
      const next = buildLocalSystemChat(message, 'error-message');

      const updated = [next, ...previous];

      if (updated.length > 1000) {
        return updated.slice(0, 500);
      }

      return updated;
    });
  }, []);

  const onSend = useCallback(
    (text: string): void => {
      setRequestParams({ message: text });
    },
    [setRequestParams]
  );

  const combinedChat = useMemo(() => {
    return {
      chat: [...localChats, ...chatMessages],
      announcements: initialAnnouncements,
    };
  }, [localChats, chatMessages, initialAnnouncements]);

  return {
    combinedChat,
    setInitialAnnouncements,
    setInitialChatHistory,
    pushSilencedMessage,
    pushPrivateMessageSent,
    pushErrorMessage,
    onSend,
  };
};

export default useChatActions;
