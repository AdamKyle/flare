import React, { useCallback, useEffect, useMemo, useState } from 'react';

import { useFetchChatHistory } from './api/hooks/use-fetch-chat-history';
import { useSendChatMessage } from './api/hooks/use-send-chat-message';
import Chat from './chat';
import AnnouncementMessages from './components/announcements/announcement-messages';
import ExplorationMessages from './components/exploration-messages/exploration-messages';
import ServerMessages from './components/server-messages/server-messages';
import { useChatStream } from './websockets/hooks/use-chat-stream';
import AnnouncementMessageDefinition from '../../api-definitions/chat/annoucement-message-definition';
import ChatType from '../../api-definitions/chat/chat-message-definition';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import PillTabs from 'ui/tabs/pill-tabs';

const GameChat = () => {
  const { gameData } = useGameData();

  const characterId = gameData?.character?.id ?? 0;

  const { data } = useFetchChatHistory({ character_id: characterId });

  const character = gameData?.character ?? null;
  const isAdmin = Boolean(character?.is_admin);
  const isSilenced = character?.is_silenced ?? null;
  const canTalkAgainAt = character?.can_talk_again_at ?? null;
  const viewPort = character?.view_port ?? 0;
  const isAutomationRunning = Boolean(character?.is_automation_running);

  const {
    server,
    exploration,
    announcements: streamAnnouncements,
    chatMessages,
    ready,
  } = useChatStream({
    characterData: character,
    view_port: viewPort,
    is_automation_running: isAutomationRunning,
  });

  const [localChats, setLocalChats] = useState<ChatType[]>([]);
  const [announcements, setAnnouncements] = useState<
    AnnouncementMessageDefinition[]
  >(data?.announcements || []);

  const { setRequestParams } = useSendChatMessage({
    character_id: characterId,
  });

  useEffect(() => {
    const initial: AnnouncementMessageDefinition[] = data?.announcements || [];
    if (initial.length === 0) {
      return;
    }
    setAnnouncements(initial);
  }, [data?.announcements]);

  useEffect(() => {
    if (!ready) {
      return;
    }
    return;
  }, [ready]);

  const setTabToUpdated = useCallback((_key: string) => {
    return;
  }, []);

  const push_silenced_message = useCallback(() => {
    setLocalChats((previous) => {
      const next = {
        message: "You child, have been chatting up a storm. Slow down. I'll let you know whe you can talk again ...",
        type: 'error-message'
      } as ChatType;

      const updated = [next, ...previous];

      if (updated.length > 1000) {
        return updated.slice(0, 500);
      }

      return updated;
    });
  }, []);

  const push_private_message_sent = useCallback((messageData: string[]) => {
    setLocalChats((previous) => {

      const next = {
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

  const push_error_message = useCallback((message: string) => {
    setLocalChats((previous) => {

      const next = {
        message,
        type: "error-message"
      } as ChatType;

      const updated = [next, ...previous];

      if (updated.length > 1000) {
        return updated.slice(0, 500);
      }

      return updated;
    });
  }, []);

  const on_send = useCallback(
    (text: string) => {
      setRequestParams({ message: text });
    },
    [setRequestParams]
  );

  const combinedChat = useMemo(() => {
    return [...localChats, ...chatMessages];
  }, [localChats, chatMessages]);

  const renderBody = () => {
    if (!character) {
      return <GameDataError />;
    }

    if (isAdmin) {
      return (
        <Chat
          is_silenced={isSilenced}
          can_talk_again_at={canTalkAgainAt}
          chat={combinedChat}
          set_tab_to_updated={setTabToUpdated}
          push_silenced_message={push_silenced_message}
          push_private_message_sent={push_private_message_sent}
          push_error_message={push_error_message}
          on_send={on_send}
        />
      );
    }

    const tabs = [
      {
        label: 'Chat',
        component: Chat,
        props: {
          is_silenced: isSilenced,
          can_talk_again_at: canTalkAgainAt,
          chat: combinedChat,
          set_tab_to_updated: setTabToUpdated,
          push_silenced_message,
          push_private_message_sent,
          push_error_message,
          on_send,
        },
      },
      {
        label: 'Server Messages',
        component: ServerMessages,
        props: {
          server_messages: server,
          character_id: character.id,
          view_port: viewPort,
          is_automation_running: isAutomationRunning,
        },
      },
      {
        label: 'Exploration',
        component: ExplorationMessages,
        props: {
          exploration_messages: exploration,
        },
      },
      {
        label: 'Announcements',
        component: AnnouncementMessages,
        props: {
          announcements: [...streamAnnouncements, ...announcements],
        },
      },
    ] as const;

    return <PillTabs tabs={tabs} additional_tab_css="w-full md:w-2/3" />;
  };

  return <div className="px-4">{renderBody()}</div>;
};

export default GameChat;
