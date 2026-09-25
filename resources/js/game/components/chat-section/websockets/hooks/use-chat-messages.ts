import { useCallback, useState } from 'react';

import EventPayload from './definitions/event-payload-definition';
import { GlobalMessagePayloadDefinition } from './definitions/global-message-payload-definition';
import { NpcMessagePayloadDefinition } from './definitions/npc-message-payload-definition';
import { PrivateMessagePayloadDefinition } from './definitions/private-message-payload-definition';
import { UseChatMessagesDefinition } from './definitions/use-chat-messages-definition';
import UseChatMessagesParams from './definitions/use-chat-messages-params';
import { ChannelType } from '../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../websocket-handler/hooks/use-websocket';
import ChatType from '../../../../api-definitions/chat/chat-message-definition';
import { ChatWebSocketChannels } from '../enums/chat-web-socket-channels';
import { ChatWebsocketEventNames } from '../enums/chat-websocket-event-names';
import {
  toChatTypeFromGlobalMessage,
  toChatTypeFromNpcMessage,
  toChatTypeFromPrivateMessage,
  toChatTypeFromPublicMessage,
} from '../utils/to-chat-type-from-event';

const MAX_CHAT_MESSAGES = 1000;

export const useChatMessages = ({
  user_id,
  include_private_channels = true,
}: UseChatMessagesParams): UseChatMessagesDefinition => {
  const [chatMessages, setChatMessages] = useState<ChatType[]>([]);

  const canSubscribeToPrivateChannels = include_private_channels && user_id > 0;

  const prependChatMessage = useCallback((next: ChatType) => {
    setChatMessages((previous) =>
      [next, ...previous].slice(0, MAX_CHAT_MESSAGES)
    );
  }, []);

  const replaceChatMessages = useCallback((messages: ChatType[]) => {
    setChatMessages(messages.slice(0, MAX_CHAT_MESSAGES));
  }, []);

  const handlePublicMessage = useCallback(
    (event: EventPayload) =>
      prependChatMessage(toChatTypeFromPublicMessage(event)),
    [prependChatMessage]
  );

  const handleGlobalMessage = useCallback(
    (event: GlobalMessagePayloadDefinition) =>
      prependChatMessage(toChatTypeFromGlobalMessage(event)),
    [prependChatMessage]
  );

  const handleNpcMessage = useCallback(
    (event: NpcMessagePayloadDefinition) =>
      prependChatMessage(toChatTypeFromNpcMessage(event)),
    [prependChatMessage]
  );

  const handlePrivateMessage = useCallback(
    (event: PrivateMessagePayloadDefinition) =>
      prependChatMessage(toChatTypeFromPrivateMessage(event)),
    [prependChatMessage]
  );

  useWebsocket<EventPayload>({
    url: ChatWebSocketChannels.CHAT,
    params: {},
    type: ChannelType.PRESENCE,
    channelName: ChatWebsocketEventNames.PUBLIC_MESSAGE,
    onEvent: handlePublicMessage,
  });

  useWebsocket<GlobalMessagePayloadDefinition>({
    url: ChatWebSocketChannels.GLOBAL_MESSAGE,
    params: {},
    type: ChannelType.PRESENCE,
    channelName: ChatWebsocketEventNames.GLOBAL_MESSAGE,
    onEvent: handleGlobalMessage,
  });

  useWebsocket<NpcMessagePayloadDefinition>({
    url: ChatWebSocketChannels.NPC_MESSAGE,
    params: { userId: user_id },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.NPC_MESSAGE,
    onEvent: handleNpcMessage,
    enabled: canSubscribeToPrivateChannels,
  });

  useWebsocket<PrivateMessagePayloadDefinition>({
    url: ChatWebSocketChannels.PRIVATE_MESSAGE,
    params: { userId: user_id },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.PRIVATE_MESSAGE,
    onEvent: handlePrivateMessage,
    enabled: canSubscribeToPrivateChannels,
  });

  return { chatMessages, prependChatMessage, replaceChatMessages };
};
