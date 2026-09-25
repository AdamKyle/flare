import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useEffect } from 'react';

import { useFetchAdminChatHistory } from './api/hooks/use-fetch-admin-chat-history';
import { useSendChatMessage } from '../../game/components/chat-section/api/hooks/use-send-chat-message';
import { useSendPrivateChatMessage } from '../../game/components/chat-section/api/hooks/use-send-private-chat-message';
import Chat from '../../game/components/chat-section/chat';
import useChatActions from '../../game/components/chat-section/hooks/use-chat-actions';
import { toChatTypeFromHistory } from '../../game/components/chat-section/utils/to-chat-type-from-history';
import { useChatMessages } from '../../game/components/chat-section/websockets/hooks/use-chat-messages';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminChatSection = (): ReactNode => {
  const { data, loading, error } = useFetchAdminChatHistory();
  const { chatMessages, prependChatMessage, replaceChatMessages } =
    useChatMessages({
      user_id: 0,
      include_private_channels: false,
    });
  const { setRequestParams } = useSendChatMessage();
  const { sendPrivateMessage, error: privateMessageError } =
    useSendPrivateChatMessage();

  const {
    combinedChat,
    setInitialChatHistory,
    pushSilencedMessage,
    pushErrorMessage,
    onSend,
  } = useChatActions({
    chatMessages,
    prependChatMessage,
    replaceChatMessages,
    setRequestParams,
    sendPrivateMessage,
  });

  useEffect(() => {
    if (privateMessageError === null) {
      return;
    }

    pushErrorMessage(privateMessageError.message);
  }, [privateMessageError, pushErrorMessage]);

  useEffect(() => {
    if (!data) {
      return;
    }

    setInitialChatHistory(data.chat_messages.map(toChatTypeFromHistory));
  }, [data, setInitialChatHistory]);

  const handleSetTabToUpdated = (): void => {};

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  return (
    <section aria-label="Public chat">
      <Chat
        chat={combinedChat.chat}
        set_tab_to_updated={handleSetTabToUpdated}
        push_silenced_message={pushSilencedMessage}
        push_error_message={pushErrorMessage}
        on_send={onSend}
        can_start_private_message
      />
    </section>
  );
};

export default AdminChatSection;
