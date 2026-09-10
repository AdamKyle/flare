import ChatType from '../../../../api-definitions/chat/chat-message-definition';
import { EventMessageTypes } from '../enums/event-message-types';
import EventPayload from '../hooks/definitions/event-payload-definition';
import { GlobalMessagePayloadDefinition } from '../hooks/definitions/global-message-payload-definition';
import { NpcMessagePayloadDefinition } from '../hooks/definitions/npc-message-payload-definition';
import { PrivateMessagePayloadDefinition } from '../hooks/definitions/private-message-payload-definition';

const CREATOR_NAME = 'The Creator';

export const toChatTypeFromPublicMessage = (event: EventPayload): ChatType => {
  const isCreatorMessage = event.name === CREATOR_NAME;

  return {
    color: event.message.color,
    map_name: event.message.map_name,
    character_name: event.name,
    message: event.message.message,
    x: Number(event.message.x_position),
    y: Number(event.message.y_position),
    type: isCreatorMessage ? EventMessageTypes.CREATOR_MESSAGE : 'chat',
    hide_location: event.message.hide_location,
    user_id: event.message.user_id,
    custom_class: event.message.custom_class,
    is_chat_bold: event.message.is_chat_bold,
    is_chat_italic: event.message.is_chat_italic,
    name_tag: event.nameTag,
  };
};

export const toChatTypeFromNpcMessage = (
  event: NpcMessagePayloadDefinition
): ChatType => ({
  color: '',
  map_name: '',
  character_name: event.npcName,
  message: event.message,
  x: 0,
  y: 0,
  type: EventMessageTypes.NPC_MESSAGE,
  hide_location: true,
  user_id: 0,
  custom_class: '',
  is_chat_bold: false,
  is_chat_italic: false,
  name_tag: null,
});

export const toChatTypeFromPrivateMessage = (
  event: PrivateMessagePayloadDefinition
): ChatType => ({
  color: '',
  map_name: '',
  character_name: event.from,
  message: event.message,
  x: 0,
  y: 0,
  type: EventMessageTypes.PRIVATE_MESSAGE_RECEIVED,
  hide_location: true,
  user_id: 0,
  custom_class: '',
  is_chat_bold: false,
  is_chat_italic: false,
  name_tag: null,
});

export const toChatTypeFromGlobalMessage = (
  event: GlobalMessagePayloadDefinition
): ChatType => ({
  color: '',
  map_name: '',
  character_name: '',
  message: event.message,
  x: 0,
  y: 0,
  type: EventMessageTypes.GLOBAL_MESSAGE,
  hide_location: true,
  user_id: 0,
  custom_class: event.specialColor ?? '',
  is_chat_bold: false,
  is_chat_italic: false,
  name_tag: null,
});
