import { useCallback, useState } from 'react';

import BaseWebSocketParams from './definitions/base-web-socket-params';
import { UseServerMessagesDefinition } from './definitions/use-server-messages-definition';
import { ChannelType } from '../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../websocket-handler/hooks/use-websocket';
import ServerMessagesDefinition from '../../../../api-definitions/chat/server-messages-definition';
import { ChatWebSocketChannels } from '../enums/chat-web-socket-channels';
import { ChatWebsocketEventNames } from '../enums/chat-websocket-event-names';

export const useServerMessages = ({
  user_id,
}: BaseWebSocketParams): UseServerMessagesDefinition => {
  const [serverMessages, setServerMessages] = useState<
    ServerMessagesDefinition[]
  >([]);

  const handleServerEvent = useCallback(
    (event: ServerMessagesDefinition) => {
      setServerMessages((previous) => [event, ...previous].slice(0, 500));
    },

    []
  );

  useWebsocket<ServerMessagesDefinition>({
    url: ChatWebSocketChannels.SERVER,
    params: { userId: user_id },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.SERVER,
    onEvent: handleServerEvent,
  });

  return { serverMessages };
};
