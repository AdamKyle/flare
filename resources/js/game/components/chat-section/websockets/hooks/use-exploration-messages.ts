import { useCallback, useEffect, useState } from 'react';

import BaseWebSocketParams from './definitions/base-web-socket-params';
import { UseExplorationMessagesDefinition } from './definitions/use-exploration-messages-definition';
import { ChannelType } from '../../../../../websocket-handler/enums/channel-type';
import { useWebsocket } from '../../../../../websocket-handler/hooks/use-websocket';
import ExplorationMessageDefinition from '../../../../api-definitions/chat/exploration-message-definition';
import ExplorationMessageRecordDefinition from '../../components/exploration-messages/definitions/exploration-message-record-definition';
import { ChatStreamEvent } from '../../events/enums/chat-stream-event';
import { useExplorationMessageEmitter } from '../../events/hooks/use-exploration-message-emitter';
import { ChatWebSocketChannels } from '../enums/chat-web-socket-channels';
import { ChatWebsocketEventNames } from '../enums/chat-websocket-event-names';

export const useExplorationMessages = ({
  user_id,
}: BaseWebSocketParams): UseExplorationMessagesDefinition => {
  const explorationMessageEmitter = useExplorationMessageEmitter();

  const [explorationMessages, setExplorationMessages] = useState<
    ExplorationMessageRecordDefinition[]
  >([]);

  const handleExplorationEvent = useCallback(
    (event: ExplorationMessageDefinition) => {
      setExplorationMessages((previous) => {
        const isAlreadyReceived = previous.some(
          (record) => record.messageId === event.messageId
        );

        if (isAlreadyReceived) {
          return previous;
        }

        const record: ExplorationMessageRecordDefinition = {
          ...event,
          id: event.messageId,
        };

        return [record, ...previous].slice(0, 500);
      });
    },

    []
  );

  useEffect(() => {
    explorationMessageEmitter.on(
      ChatStreamEvent.EXPLORATION_MESSAGE_RECEIVED,
      handleExplorationEvent
    );

    return () => {
      explorationMessageEmitter.off(
        ChatStreamEvent.EXPLORATION_MESSAGE_RECEIVED,
        handleExplorationEvent
      );
    };
  }, [explorationMessageEmitter, handleExplorationEvent]);

  useWebsocket<ExplorationMessageDefinition>({
    url: ChatWebSocketChannels.AUTOMATION_LOG,
    params: { userId: user_id },
    type: ChannelType.PRIVATE,
    channelName: ChatWebsocketEventNames.AUTOMATION_LOG,
    onEvent: handleExplorationEvent,
    enabled: user_id > 0,
  });

  return { explorationMessages };
};
