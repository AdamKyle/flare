import { RegularMessagePayloadDefinition } from './regular-message-payload-definition';

export default interface EventPayload {
  message: RegularMessagePayloadDefinition;
  nameTag: string | null;
  name: string;
  type: string; // TODO: Potentially remove.
}
