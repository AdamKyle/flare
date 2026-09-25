import clsx from 'clsx';

import ChatType from '../../../../../api-definitions/chat/chat-message-definition';
import { EventMessageTypes } from '../../../websockets/enums/event-message-types';

export const creatorMessageStyle = 'font-bold text-marigold-300 text-lg';

export const globalMessageStyle = 'font-bold italic text-marigold-300';

export const buildChatMessageClassName = (row: ChatType): string => {
  if (row.type === EventMessageTypes.CREATOR_MESSAGE) {
    return creatorMessageStyle;
  }

  if (row.type === EventMessageTypes.GLOBAL_MESSAGE) {
    return globalMessageStyle;
  }

  return clsx(row.custom_class, {
    'font-bold': row.is_chat_bold,
    italic: row.is_chat_italic,
  });
};

export const resolveChatMessageColor = (row: ChatType): string | undefined => {
  if (row.type !== 'chat') {
    return undefined;
  }

  if (row.custom_class) {
    return undefined;
  }

  return row.color || undefined;
};
