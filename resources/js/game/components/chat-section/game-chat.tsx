import React, { useCallback, useEffect, useMemo, useState } from 'react';

import { useFetchChatHistory } from './api/hooks/use-fetch-chat-history';
import { useSendChatMessage } from './api/hooks/use-send-chat-message';
import Chat from './chat';
import AnnouncementMessages from './components/announcements/announcement-messages';
import ExplorationMessages from './components/exploration-messages/exploration-messages';
import ServerMessages from './components/server-messages/server-messages';
import { useChatStream } from './websockets/hooks/use-chat-stream';
import AnnouncementMessageDefinition from '../../api-definitions/chat/annoucement-message-definition';
import ChatType, {
  ChatMessageType,
} from '../../api-definitions/chat/chat-message-definition';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import PillTabs from 'ui/tabs/pill-tabs';

type SystemRow = {
  type: Exclude<ChatMessageType, 'chat'>;
  message: string | null | undefined;
};

type ChatRow = {
  type: 'chat';
  color?: string | null;
  map_name?: string | null;
  character_name?: string | null;
  message?: string | null;
  x?: number | string | null;
  y?: number | string | null;
  hide_location?: boolean | null;
};

type _StreamRow = ChatRow | SystemRow;

const makeSystemChat = (message: string, type: ChatMessageType): ChatType => {
  return {
    color: '',
    map_name: '',
    character_name: '',
    message,
    x: 0,
    y: 0,
    type,
    hide_location: true,
  };
};

const isObject = (value: unknown): value is Record<string, unknown> => {
  return typeof value === 'object' && value !== null;
};

const isChatRow = (row: unknown): row is ChatRow => {
  if (!isObject(row)) {
    return false;
  }

  if (!('type' in row) || (row as { type?: unknown }).type !== 'chat') {
    return false;
  }

  return true;
};

const isSystemRow = (row: unknown): row is SystemRow => {
  if (!isObject(row)) {
    return false;
  }

  if (!('type' in row)) {
    return false;
  }

  const typeValue = (row as { type?: unknown }).type;
  return (
    typeValue === 'creator-message' ||
    typeValue === 'global-message' ||
    typeValue === 'error-message' ||
    typeValue === 'private-message-sent'
  );
};

const coerceStreamToChatType = (row: unknown): ChatType => {
  if (isChatRow(row)) {
    return {
      color: (row.color ?? '') as string,
      map_name: (row.map_name ?? '') as string,
      character_name: (row.character_name ?? '') as string,
      message: (row.message ?? '') as string,
      x: Number(row.x ?? 0),
      y: Number(row.y ?? 0),
      type: 'chat',
      hide_location: Boolean(row.hide_location),
    };
  }

  if (isSystemRow(row)) {
    const sys = row as SystemRow;
    return makeSystemChat(String(sys.message ?? ''), sys.type);
  }

  return makeSystemChat(String(row ?? ''), 'error-message');
};

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
      const next = makeSystemChat(
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

  const push_private_message_sent = useCallback((messageData: string[]) => {
    setLocalChats((previous) => {
      const next = makeSystemChat(
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

  const push_error_message = useCallback((message: string) => {
    setLocalChats((previous) => {
      const next = makeSystemChat(message, 'error-message');

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

  const streamedChatAsChatType = useMemo(() => {
    // Now strongly typed: chatMessages is ChatType[] from the hook definition
    return chatMessages.map((row) => coerceStreamToChatType(row));
  }, [chatMessages]);

  const combinedChat = useMemo(() => {
    return [...localChats, ...streamedChatAsChatType];
  }, [localChats, streamedChatAsChatType]);

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
