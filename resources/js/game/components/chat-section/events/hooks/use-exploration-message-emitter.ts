import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseExplorationMessageEmitterDefinition from './definitions/use-exploration-message-emitter-definition';
import { ChatStreamEvent } from '../enums/chat-stream-event';
import ChatStreamEventMap from '../event-map/chat-stream-event-map';

export const useExplorationMessageEmitter =
  (): UseExplorationMessageEmitterDefinition => {
    const eventSystem = useEventSystem();

    return eventSystem.fetchOrCreateEventEmitter<ChatStreamEventMap>(
      ChatStreamEvent.EXPLORATION_MESSAGE_RECEIVED
    );
  };
