import { useCallback, useState } from 'react';

import BaseWebSocketParams from './definitions/base-web-socket-params';
import { UseExplorationMessagesDefinition } from './definitions/use-exploration-messages-definition';
import { ChannelType } from '../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../websocket-handler/hooks/use-websocket';
import ExplorationMessageDefinition from '../../../../api-definitions/chat/exploration-message-definition';
import { ChatWebSocketChannels } from '../enums/chat-web-socket-channels';
import { ChatWebsocketEventNames } from '../enums/chat-websocket-event-names';

export const useExplorationMessages = ({
  user_id,
}: BaseWebSocketParams): UseExplorationMessagesDefinition => {
  const [explorationMessages, setExplorationMessages] = useState<
    ExplorationMessageDefinition[]
  >([]);

  const handleExplorationEvent = useCallback(
    (event: ExplorationMessageDefinition) => {
      setExplorationMessages((previous) => [event, ...previous].slice(0, 500));
    },

    []
  );

  useWebsocket<ExplorationMessageDefinition>({
    url: ChatWebSocketChannels.EXPLORATION,
    params: { userId: user_id },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.EXPLORATION,
    onEvent: handleExplorationEvent,
  });

  return { explorationMessages };
};
