import ChatType, {
  ChatMessageType,
} from '../../../../api-definitions/chat/chat-message-definition';
import { EventMessageTypes } from '../enums/event-message-types';
import EventPayload from '../hooks/definitions/event-payload-definition';
import { RegularMessagePayloadDefinition } from '../hooks/definitions/regular-message-payload-definition';

const isRegularPayload = (
  message: EventPayload['message']
): message is RegularMessagePayloadDefinition => {
  if (typeof message !== 'object' || message === null) {
    return false;
  }

  if (!('message' in message)) {
    return false;
  }

  const candidate = message as { message?: unknown };

  return typeof candidate.message === 'string';
};

const extractMessageText = (message: EventPayload['message']): string => {
  if (typeof message === 'object' && message !== null && 'message' in message) {
    const candidate = (message as { message?: unknown }).message;

    if (typeof candidate === 'string') {
      return candidate;
    }

    if (candidate != null) {
      return String(candidate);
    }
  }

  return '';
};

const toChatTypeFromRegularPayload = (
  payload: RegularMessagePayloadDefinition,
  chatType: ChatMessageType
): ChatType => {
  return {
    color: payload.color,
    map_name: payload.map_name,
    character_name: payload.name,
    message: payload.message,
    x: Number(payload.x_position),
    y: Number(payload.y_position),
    type: chatType,
    hide_location: payload.hide_location,
    user_id: payload.user_id,
    custom_class: payload.custom_class,
    is_chat_bold: payload.is_chat_bold,
    is_chat_italic: payload.is_chat_italic,
    name_tag: payload.nameTag,
  };
};

const toCreatorChat = (message: string | { message: string }): ChatType => {
  const text = typeof message === 'string' ? message : message.message;

  return {
    color: '',
    map_name: '',
    character_name: 'The Creator',
    message: text,
    x: 0,
    y: 0,
    type: EventMessageTypes.CREATOR_MESSAGE as ChatMessageType,
    hide_location: true,
    is_chat_bold: false,
    is_chat_italic: false,
    user_id: 0,
    custom_class: '',
    name_tag: '',
  };
};

const toSystemChat = (message: string, type: ChatMessageType): ChatType => {
  return {
    color: '',
    map_name: '',
    character_name: '',
    message,
    x: 0,
    y: 0,
    type,
    hide_location: true,
    is_chat_bold: false,
    is_chat_italic: false,
    user_id: 0,
    custom_class: '',
    name_tag: '',
  };
};

export const toChatTypeFromEvent = (event: EventPayload): ChatType => {
  if (event.type === EventMessageTypes.CREATOR_MESSAGE) {
    return toCreatorChat(event.message);
  }

  if (
    [
      EventMessageTypes.GLOBAL_MESSAGE,
      EventMessageTypes.ERROR_MESSAGE,
      EventMessageTypes.PRIVATE_MESSAGE_SENT,
    ].includes(event.type)
  ) {
    const systemMessage = extractMessageText(event.message);

    return toSystemChat(systemMessage, event.type as ChatMessageType);
  }

  if (event.type === EventMessageTypes.NPC_MESSAGE) {
    const npcMessage = extractMessageText(event.message);

    return toSystemChat(
      npcMessage,
      EventMessageTypes.GLOBAL_MESSAGE as ChatMessageType
    );
  }

  if (isRegularPayload(event.message)) {
    return toChatTypeFromRegularPayload(
      event.message,
      event.type as ChatMessageType
    );
  }

  const fallbackMessage = extractMessageText(event.message);

  return toSystemChat(
    fallbackMessage,
    EventMessageTypes.ERROR_MESSAGE as ChatMessageType
  );
};
