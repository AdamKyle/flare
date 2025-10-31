import { useCallback, useMemo, useState } from 'react';

import UseChatActionsDefinition from './definitions/use-chat-actions-definition';
import UseChatActionsParamsDefinition from './definitions/use-chat-actions-params-definition';
import AnnouncementMessageDefinition from '../../../api-definitions/chat/annoucement-message-definition';
import ChatType from '../../../api-definitions/chat/chat-message-definition';

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

  const pushSilencedMessage = useCallback((): void => {
    setLocalChats((previous) => {
      const next: ChatType = {
        message:
          "You child, have been chatting up a storm. Slow down. I'll let you know whe you can talk again ...",
        type: 'error-message',
      } as ChatType;

      const updated = [next, ...previous];

      if (updated.length > 1000) {
        return updated.slice(0, 500);
      }

      return updated;
    });
  }, []);

  const pushPrivateMessageSent = useCallback((messageData: string[]): void => {
    setLocalChats((previous) => {
      const next: ChatType = {
        message: `Sent to ${messageData[1]}: ${messageData[2]}`,
        type: 'private-message-sent',
      } as ChatType;

      const updated = [next, ...previous];

      if (updated.length > 1000) {
        return updated.slice(0, 500);
      }

      return updated;
    });
  }, []);

  const pushErrorMessage = useCallback((message: string): void => {
    setLocalChats((previous) => {
      const next: ChatType = {
        message,
        type: 'error-message',
      } as ChatType;

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
