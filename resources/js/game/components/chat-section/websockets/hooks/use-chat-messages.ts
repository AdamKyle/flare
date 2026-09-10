import { useCallback, useState } from 'react';

import BaseWebSocketParams from './definitions/base-web-socket-params';
import EventPayload from './definitions/event-payload-definition';
import { GlobalMessagePayloadDefinition } from './definitions/global-message-payload-definition';
import { NpcMessagePayloadDefinition } from './definitions/npc-message-payload-definition';
import { PrivateMessagePayloadDefinition } from './definitions/private-message-payload-definition';
import { UseChatMessagesDefinition } from './definitions/use-chat-messages-definition';
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
}: BaseWebSocketParams): UseChatMessagesDefinition => {
  const [chatMessages, setChatMessages] = useState<ChatType[]>([]);

  const pushChatMessage = useCallback((next: ChatType) => {
    setChatMessages((previous) =>
      [next, ...previous].slice(0, MAX_CHAT_MESSAGES)
    );
  }, []);

  const handlePublicMessage = useCallback(
    (event: EventPayload) =>
      pushChatMessage(toChatTypeFromPublicMessage(event)),
    [pushChatMessage]
  );

  const handleGlobalMessage = useCallback(
    (event: GlobalMessagePayloadDefinition) =>
      pushChatMessage(toChatTypeFromGlobalMessage(event)),
    [pushChatMessage]
  );

  const handleNpcMessage = useCallback(
    (event: NpcMessagePayloadDefinition) =>
      pushChatMessage(toChatTypeFromNpcMessage(event)),
    [pushChatMessage]
  );

  const handlePrivateMessage = useCallback(
    (event: PrivateMessagePayloadDefinition) =>
      pushChatMessage(toChatTypeFromPrivateMessage(event)),
    [pushChatMessage]
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
    enabled: user_id > 0,
  });

  useWebsocket<PrivateMessagePayloadDefinition>({
    url: ChatWebSocketChannels.PRIVATE_MESSAGE,
    params: { userId: user_id },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.PRIVATE_MESSAGE,
    onEvent: handlePrivateMessage,
    enabled: user_id > 0,
  });

  return { chatMessages };
};
