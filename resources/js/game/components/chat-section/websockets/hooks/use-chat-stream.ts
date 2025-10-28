import { useCallback, useEffect, useMemo, useState } from 'react';

import EventPayload from './definitions/event-payload-definition';
import RegularChatEventDefinition from './definitions/regular-chat-event-definition';
import { RegularMessagePayloadDefinition } from './definitions/regular-message-payload-definition';
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

const isRegularPayload = (
  value: unknown
): value is RegularMessagePayloadDefinition => {
  if (typeof value !== 'object' || value === null) {
    return false;
  }
  return 'message' in value;
};

const toChatTypeFromRegularPayload = (
  payload: RegularMessagePayloadDefinition,
  chatType: ChatMessageType
): ChatType => {
  console.log('toChatTypeFromRegularPayload', payload);

  return {
    color: payload.color,
    map_name: payload.map_name,
    character_name: payload.name,
    message: payload.message,
    x: Number(payload.x_position),
    y: Number(payload.y_position),
    type: chatType,
    hide_location: payload.hide_location,
    user_id: payload.user_id,
    custom_class: payload.custom_class,
    is_chat_bold: payload.is_chat_bold,
    is_chat_italic: payload.is_chat_italic,
    name_tag: payload.nameTag,
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
    is_chat_bold: false,
    is_chat_italic: false,
    user_id: 0,
    custom_class: '',
    name_tag: '',
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
    is_chat_bold: false,
    is_chat_italic: false,
    user_id: 0,
    custom_class: '',
    name_tag: '',
  };
};

export const useChatStream = (
  params?: UseChatStreamParams
): UseChatStreamDefinition => {
  const [server, setServer] = useState<ServerMessagesDefinition[]>([]);
  const [exploration, setExploration] = useState<
    ExplorationMessageDefinition[]
  >([]);
  const [announcements, setAnnouncements] = useState<
    AnnouncementMessageDefinition[]
  >([]);
  const [chatMessages, setChatMessages] = useState<ChatType[]>([]);
  const [ready, setReady] = useState(false);

  const onServerEvent = useCallback((event: ServerMessagesDefinition) => {
    setServer((previous) => [event, ...previous].slice(0, 500));
  }, []);

  const onExplorationEvent = useCallback(
    (event: ExplorationMessageDefinition) => {
      setExploration((previous) => [event, ...previous].slice(0, 500));
    },
    []
  );

  const onAnnouncementEvent = useCallback(
    (event: AnnouncementMessageDefinition) => {
      setAnnouncements((previous) => [event, ...previous].slice(0, 500));
    },
    []
  );

  const onChatSent = useCallback((event: EventPayload) => {
    console.log('onChatSent', event);

    let next: ChatType;

    if (event.type === 'creator-message') {
      next = toCreatorChat(event.message);
    } else if (
      event.type === 'global-message' ||
      event.type === 'error-message' ||
      event.type === 'private-message-sent'
    ) {
      next = toSystemChat(event.message.message as string, event.type);
    } else if (event.type === 'npc-message') {
      // Map NPC messages to a display-safe system type present in ChatMessageType
      next = toSystemChat(event.message.message, 'global-message');
    } else {
      // Regular chat payload
      const regular = event as RegularChatEventDefinition;
      if (isRegularPayload(regular.message)) {
        const message = {
          ...regular.message,
          name: event.name,
          nameTag: event.nameTag,
        };
        next = toChatTypeFromRegularPayload(message, regular.type);
      } else {
        next = toSystemChat(
          String((regular as unknown as { message: string }).message),
          'error-message'
        );
      }
    }

    setChatMessages((previous) => [next, ...previous].slice(0, 1000));
  }, []);

  const userId = params?.characterData?.id ?? 0;

  useWebsocket<ServerMessagesDefinition>({
    url: ChatWebSocketChannels.SERVER,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.SERVER,
    onEvent: onServerEvent,
  });

  useWebsocket<ExplorationMessageDefinition>({
    url: ChatWebSocketChannels.EXPLORATION,
    params: { userId },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.EXPLORATION,
    onEvent: onExplorationEvent,
  });

  useWebsocket<AnnouncementMessageDefinition>({
    url: ChatWebSocketChannels.ANNOUNCEMENTS,
    params: {},
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.ANNOUNCEMENT,
    onEvent: onAnnouncementEvent,
  });

  useWebsocket<EventPayload>({
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
