import EventMapDefinition from 'event-system/deffintions/event-map-definition';

import ExplorationMessageDefinition from '../../../../api-definitions/chat/exploration-message-definition';
import { ChatStreamEvent } from '../enums/chat-stream-event';

export default interface ChatStreamEventMap extends EventMapDefinition {
  [ChatStreamEvent.EXPLORATION_MESSAGE_RECEIVED]: ExplorationMessageDefinition;
}
