import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { useEffect } from 'react';

import { useFetchChatHistory } from './api/hooks/use-fetch-chat-history';
import { useSendChatMessage } from './api/hooks/use-send-chat-message';
import Chat from './chat';
import ExplorationMessages from './components/exploration-messages/exploration-messages';
import ServerMessages from './components/server-messages/server-messages';
import useChatActions from './hooks/use-chat-actions';
import useUnreadBadges from './hooks/use-unread-badges';
import buildTabs from './utils/build-tabs';
import { useChatStream } from './websockets/hooks/use-chat-stream';
import AnnouncementMessageDefinition from '../../api-definitions/chat/annoucement-message-definition';
import ChatType from '../../api-definitions/chat/chat-message-definition';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import PillTabs from 'ui/tabs/pill-tabs';

const GameChat = () => {
  const { gameData } = useGameData();

  const { data, loading, error } = useFetchChatHistory();

  const character = gameData?.character ?? null;
  const isAdmin = Boolean(character?.is_admin);
  const isSilenced = character?.is_silenced ?? null;
  const canTalkAgainAt = character?.can_talk_again_at ?? null;

  const { server, exploration, chatMessages } = useChatStream({
    character_data: character,
  });

  const { setRequestParams } = useSendChatMessage();

  const {
    combinedChat,
    setInitialAnnouncements,
    setInitialChatHistory,
    pushSilencedMessage,
    pushPrivateMessageSent,
    pushErrorMessage,
    onSend,
  } = useChatActions({
    chatMessages,
    setRequestParams,
  });

  useEffect(() => {
    const initial: AnnouncementMessageDefinition[] = data?.announcements || [];

    if (initial.length === 0) {
      return;
    }

    setInitialAnnouncements(initial);
  }, [data, setInitialAnnouncements]);

  useEffect(() => {
    if (!data) {
      return;
    }

    const chatHistory: ChatType[] = data.chat_messages.map((chatMessage) => {
      return {
        color: chatMessage.color,
        map_name: chatMessage.map,
        character_name: chatMessage.name,
        message: chatMessage.message,
        x: chatMessage.x_position,
        y: chatMessage.y_position,
        type: 'chat',
        hide_location: chatMessage.hide_location,
        user_id: chatMessage.user_id,
        custom_class: chatMessage.custom_class,
        is_chat_bold: chatMessage.is_chat_bold,
        is_chat_italic: chatMessage.is_chat_italic,
        name_tag: chatMessage.name_tag,
      };
    });

    setInitialChatHistory(chatHistory);
  }, [data, setInitialChatHistory]);

  const { unreadServer, activeTabIndex, handleActiveIndexChange } =
    useUnreadBadges({
      serverCount: server.length,
      serverIndex: 1,
      initialActiveIndex: 0,
    });

  const renderBody = () => {
    if (!character) {
      return <GameDataError />;
    }

    if (isAdmin) {
      return (
        <Chat
          is_silenced={isSilenced}
          can_talk_again_at={canTalkAgainAt}
          chat={combinedChat.chat}
          set_tab_to_updated={() => {}}
          push_silenced_message={pushSilencedMessage}
          push_private_message_sent={pushPrivateMessageSent}
          push_error_message={pushErrorMessage}
          on_send={onSend}
        />
      );
    }

    const tabs = buildTabs({
      chatComponent: Chat,
      serverComponent: ServerMessages,
      explorationComponent: ExplorationMessages,
      bellIconClass: 'far fa-bell',
      bellIconStyles: 'text-mango-tango-600 dark:text-mango-tango-300',
      chatProps: {
        is_silenced: isSilenced,
        can_talk_again_at: canTalkAgainAt,
        chat: combinedChat.chat,
        set_tab_to_updated: () => {},
        push_silenced_message: pushSilencedMessage,
        push_private_message_sent: pushPrivateMessageSent,
        push_error_message: pushErrorMessage,
        on_send: onSend,
      },
      serverProps: {
        server_messages: server,
        character_id: character!.id,
      },
      explorationProps: {
        exploration_messages: exploration,
      },
      unreadServer,
    });

    return (
      <PillTabs
        tabs={tabs}
        additional_tab_css="w-full lg:w-2/3"
        onActiveIndexChange={handleActiveIndexChange}
        initialIndex={activeTabIndex}
      />
    );
  };

  if (loading) {
    return (
      <div className="mx-auto w-full md:w-2/3">
        <InfiniteLoader />
      </div>
    );
  }

  if (error) {
    return (
      <div className="mx-auto w-full md:w-2/3">
        <ApiErrorAlert apiError={error.message} />
      </div>
    );
  }

  return renderBody();
};

export default GameChat;
