import { RegularMessagePayloadDefinition } from './regular-message-payload-definition';

export default interface EventPayload {
  message: RegularMessagePayloadDefinition;
  name: string;
  nameTag: string | null;
}
