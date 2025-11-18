import { RegularMessagePayloadDefinition } from './regular-message-payload-definition';
import { EventMessageTypes } from '../../enums/event-message-types';

export default interface EventPayload {
  message: RegularMessagePayloadDefinition;
  nameTag: string | null;
  name: string;
  type: EventMessageTypes;
}
