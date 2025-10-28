import { useCallback, useEffect, useMemo, useState } from 'react';

import UseChatStreamParams from './definitions/use-chat-stream-params';
import { ChannelType } from '../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../websocket-handler/hooks/use-websocket';
import AnnouncementMessageDefinition from '../../../../api-definitions/chat/annoucement-message-definition';
import ChatType, {
  ChatMessageType,
} from '../../../../api-definitions/chat/chat-message-definition';
import ExplorationMessageDefinition from '../../../../api-definitions/chat/exploration-message-definition';
import { ChatWebSocketChannels } from '../enums/chat-web-socket-channels';
import { ChatWebsocketEventNames } from '../enums/chat-websocket-event-names';
import { UseChatStreamDefinition } from './definitions/use-chat-stream-definition';
import ServerMessagesDefinition from '../../../../api-definitions/chat/server-messages-definition';

type RegularMessagePayload = {
  message: string;
  color?: string | null;
  map_name?: string | null;
  x_position?: number | null;
  y_position?: number | null;
  hide_location?: boolean | null;
  is_chat_bold?: boolean | null;
  is_chat_italic?: boolean | null;
  character_name?: string | null;
};

type RegularChatEvent = {
  type: ChatMessageType;
  message: RegularMessagePayload;
};

// The incoming socket event types we care about for the PUBLIC channel:
type ChatSentEvent =
  | { type: 'creator-message'; message: string | { message: string } }
  | { type: 'global-message'; message: string }
  | { type: 'npc-message'; message: string }
  | { type: 'error-message'; message: string }
  | { type: 'private-message-sent'; message: string }
  | RegularChatEvent;

type ServerEvent = ServerMessagesDefinition;
type ExplorationEvent = ExplorationMessageDefinition;
type AnnouncementEvent = AnnouncementMessageDefinition;

const isRegularPayload = (value: unknown): value is RegularMessagePayload => {
  if (typeof value !== 'object' || value === null) {
    return false;
  }
  return 'message' in value;
};

const toChatTypeFromRegularPayload = (
  payload: RegularMessagePayload,
  chatType: ChatMessageType
): ChatType => {
  return {
    color: payload.color ?? '',
    map_name: payload.map_name ?? '',
    character_name: payload.character_name ?? '',
    message: payload.message,
    x: Number(payload.x_position ?? 0),
    y: Number(payload.y_position ?? 0),
    type: chatType,
    hide_location: Boolean(payload.hide_location),
  };
};

const toCreatorChat = (message: string | { message: string }): ChatType => {
  const text = typeof message === 'string' ? message : message.message;
  return {
    color: '',
    map_name: '',
    character_name: 'The Creator',
    message: text,
    x: 0,
    y: 0,
    type: 'creator-message',
    hide_location: true,
  };
};

const toSystemChat = (
  message: string,
  type: Exclude<ChatMessageType, 'chat'>
): ChatType => {
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

export const useChatStream = (
  params?: UseChatStreamParams
): UseChatStreamDefinition => {
  const [server, setServer] = useState<ServerEvent[]>([]);
  const [exploration, setExploration] = useState<ExplorationEvent[]>([]);
  const [announcements, setAnnouncements] = useState<AnnouncementEvent[]>([]);
  const [chatMessages, setChatMessages] = useState<ChatType[]>([]);
  const [ready, setReady] = useState(false);

  const onServerEvent = useCallback((event: ServerEvent) => {
    setServer((previous) => [event, ...previous].slice(0, 500));
  }, []);

  const onExplorationEvent = useCallback((event: ExplorationEvent) => {
    setExploration((previous) => [event, ...previous].slice(0, 500));
  }, []);

  const onAnnouncementEvent = useCallback((event: AnnouncementEvent) => {
    setAnnouncements((previous) => [event, ...previous].slice(0, 500));
  }, []);

  const onChatSent = useCallback((event: ChatSentEvent) => {
    let next: ChatType;

    if (event.type === 'creator-message') {
      next = toCreatorChat(event.message);
    } else if (
      event.type === 'global-message' ||
      event.type === 'error-message' ||
      event.type === 'private-message-sent'
    ) {
      next = toSystemChat(event.message as string, event.type);
    } else if (event.type === 'npc-message') {
      // Map NPC messages to a display-safe system type present in ChatMessageType
      next = toSystemChat(event.message, 'global-message');
    } else {
      // Regular chat payload
      const regular = event as RegularChatEvent;
      if (isRegularPayload(regular.message)) {
        next = toChatTypeFromRegularPayload(regular.message, regular.type);
      } else {
        next = toSystemChat(
          String((regular as unknown as { message: string }).message),
          'error-message'
        );
      }
    }

    setChatMessages((previous) => [next, ...previous].slice(0, 1000));
  }, []);

  // Only pass numeric params to satisfy UseWebsocketParams.params: Record<string, number>
  const userId = params?.characterData?.id ?? 0;

  useWebsocket<ServerEvent>({
    url: ChatWebSocketChannels.SERVER,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.SERVER,
    onEvent: onServerEvent,
  });

  useWebsocket<ExplorationEvent>({
    url: ChatWebSocketChannels.EXPLORATION,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.EXPLORATION,
    onEvent: onExplorationEvent,
  });

  useWebsocket<AnnouncementEvent>({
    url: ChatWebSocketChannels.ANNOUNCEMENTS,
    params: {},
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.ANNOUNCEMENT,
    onEvent: onAnnouncementEvent,
  });

  useWebsocket<ChatSentEvent>({
    url: ChatWebSocketChannels.CHAT,
    params: {},
    type: ChannelType.PUBLIC,
    channelName: ChatWebsocketEventNames.PUBLIC_MESSAGE,
    onEvent: onChatSent,
  });

  useEffect(() => {
    setReady(true);
  }, []);

  return useMemo(
    () => ({
      server,
      exploration,
      announcements,
      chatMessages,
      ready,
    }),
    [server, exploration, announcements, chatMessages, ready]
  );
};
