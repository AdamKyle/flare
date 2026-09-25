import ChatType from '../../../api-definitions/chat/chat-message-definition';
import { PublicChatHistoryDefinition } from '../api/hooks/definitions/public-chat-history-definition';
import { ChatSenderNames } from '../enums/chat-sender-names';
import { EventMessageTypes } from '../websockets/enums/event-message-types';

export const toChatTypeFromHistory = (
  chatMessage: PublicChatHistoryDefinition
): ChatType => ({
  color: chatMessage.color,
  map_name: chatMessage.map,
  character_name: chatMessage.name,
  message: chatMessage.message,
  x: chatMessage.x_position,
  y: chatMessage.y_position,
  type:
    chatMessage.name === ChatSenderNames.CREATOR
      ? EventMessageTypes.CREATOR_MESSAGE
      : 'chat',
  hide_location: chatMessage.hide_location,
  user_id: chatMessage.user_id,
  custom_class: chatMessage.custom_class,
  is_chat_bold: chatMessage.is_chat_bold,
  is_chat_italic: chatMessage.is_chat_italic,
  name_tag: chatMessage.name_tag,
  created_at: chatMessage.created_at,
});
