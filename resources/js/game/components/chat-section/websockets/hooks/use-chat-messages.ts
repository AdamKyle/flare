import { useCallback, useState } from 'react';

import EventPayload from './definitions/event-payload-definition';
import { UseChatMessagesDefinition } from './definitions/use-chat-messages-definition';
import { ChannelType } from '../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../websocket-handler/hooks/use-websocket';
import ChatType from '../../../../api-definitions/chat/chat-message-definition';
import { ChatWebSocketChannels } from '../enums/chat-web-socket-channels';
import { ChatWebsocketEventNames } from '../enums/chat-websocket-event-names';
import { toChatTypeFromEvent } from '../utils/to-chat-type-from-event';

export const useChatMessages = (): UseChatMessagesDefinition => {
  const [chatMessages, setChatMessages] = useState<ChatType[]>([]);

  const handleChatSent = useCallback(
    (event: EventPayload) => {
      const next = toChatTypeFromEvent(event);

      setChatMessages((previous) => [next, ...previous].slice(0, 1000));
    },

    []
  );

  useWebsocket<EventPayload>({
    url: ChatWebSocketChannels.CHAT,
    params: {},
    type: ChannelType.PUBLIC,
    channelName: ChatWebsocketEventNames.PUBLIC_MESSAGE,
    onEvent: handleChatSent,
  });

  return { chatMessages };
};
