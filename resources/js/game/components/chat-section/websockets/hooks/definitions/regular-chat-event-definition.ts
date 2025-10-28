import { RegularMessagePayloadDefinition } from './regular-message-payload-definition';
import { ChatMessageType } from '../../../../../api-definitions/chat/chat-message-definition';

export default interface RegularChatEventDefinition {
  type: Exclude<
    ChatMessageType,
    | 'creator-message'
    | 'global-message'
    | 'error-message'
    | 'private-message-sent'
    | 'npc-message'
  >;
  message: RegularMessagePayloadDefinition;
}
