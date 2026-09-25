import { useCallback, useMemo, useState } from 'react';

import UseChatActionsDefinition from './definitions/use-chat-actions-definition';
import UseChatActionsParamsDefinition from './definitions/use-chat-actions-params-definition';
import AnnouncementMessageDefinition from '../../../api-definitions/chat/annoucement-message-definition';
import ChatType, {
  ChatMessageType,
} from '../../../api-definitions/chat/chat-message-definition';
import SendPrivateChatMessageRequest from '../api/hooks/definitions/send-private-chat-message-request';
import { parsePrivateMessageCommand } from '../utils/parse-private-message-command';

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
  created_at: null,
});

const useChatActions = (
  params: UseChatActionsParamsDefinition
): UseChatActionsDefinition => {
  const {
    chatMessages,
    prependChatMessage,
    replaceChatMessages,
    setRequestParams,
    sendPrivateMessage,
  } = params;

  const [initialAnnouncements, setInitialAnnouncements] = useState<
    AnnouncementMessageDefinition[]
  >([]);

  const pushSilencedMessage = useCallback((): void => {
    prependChatMessage(
      buildLocalSystemChat(
        "You child, have been chatting up a storm. Slow down. I'll let you know whe you can talk again ...",
        'error-message'
      )
    );
  }, [prependChatMessage]);

  const pushPrivateMessageSent = useCallback(
    (request: SendPrivateChatMessageRequest): void => {
      prependChatMessage(
        buildLocalSystemChat(
          `Sent to ${request.user_name}: ${request.message}`,
          'private-message-sent'
        )
      );
    },
    [prependChatMessage]
  );

  const pushErrorMessage = useCallback(
    (message: string): void => {
      prependChatMessage(buildLocalSystemChat(message, 'error-message'));
    },
    [prependChatMessage]
  );

  const sendPrivate = useCallback(
    async (request: SendPrivateChatMessageRequest): Promise<void> => {
      const delivered = await sendPrivateMessage(request);

      if (!delivered) {
        return;
      }

      pushPrivateMessageSent(request);
    },
    [pushPrivateMessageSent, sendPrivateMessage]
  );

  const onSend = useCallback(
    (text: string): void => {
      const privateMessageRequest = parsePrivateMessageCommand(text);

      if (privateMessageRequest === null) {
        setRequestParams({ message: text });

        return;
      }

      sendPrivate(privateMessageRequest).catch(() => {
        pushErrorMessage('Unable to send the private message.');
      });
    },
    [pushErrorMessage, sendPrivate, setRequestParams]
  );

  const combinedChat = useMemo(() => {
    return {
      chat: chatMessages,
      announcements: initialAnnouncements,
    };
  }, [chatMessages, initialAnnouncements]);

  return {
    combinedChat,
    setInitialAnnouncements,
    setInitialChatHistory: replaceChatMessages,
    pushSilencedMessage,
    pushErrorMessage,
    onSend,
  };
};

export default useChatActions;
